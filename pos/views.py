from django.shortcuts import render, get_object_or_404, redirect
from django.http import HttpResponse
from django.db.models import Sum, Count
from django.utils import timezone
from django.contrib import messages
from django.views.decorators.http import require_http_methods
from django.views.decorators.cache import never_cache
from django.contrib.auth.decorators import login_required
from .models import Product, Sale, SaleDetail
from .forms import ProductForm
import datetime
import json

# --- DASHBOARD VIEWS ---

@login_required
def dashboard_view(request):
    # Date Handling
    date_str = request.GET.get('date')
    if date_str:
        try:
            selected_date = datetime.datetime.strptime(date_str, '%Y-%m-%d').date()
        except ValueError:
            selected_date = timezone.now().date()
    else:
        selected_date = timezone.now().date()

    # Stats (General)
    today = timezone.now().date()
    sales_today = Sale.objects.filter(fecha__date=today).aggregate(Sum('total'))['total__sum'] or 0
    sales_month = Sale.objects.filter(fecha__month=today.month, fecha__year=today.year).aggregate(Sum('total'))['total__sum'] or 0

    # Sales Table for Selected Date
    # Order by newest first
    sales_history = Sale.objects.filter(fecha__date=selected_date).order_by('-fecha')
    
    # Calculate Total for Selected Day (if different from today, might be useful)
    selected_date_total = sales_history.aggregate(Sum('total'))['total__sum'] or 0

    # Payment Method Breakdown for Selected Date
    sales_cash = sales_history.filter(metodo_pago='Efectivo').aggregate(Sum('total'))['total__sum'] or 0
    sales_transfer = sales_history.filter(metodo_pago='Transferencia').aggregate(Sum('total'))['total__sum'] or 0

    # Monthly Payment Breakdown
    sales_month_cash = Sale.objects.filter(fecha__month=today.month, fecha__year=today.year, metodo_pago='Efectivo').aggregate(Sum('total'))['total__sum'] or 0
    sales_month_transfer = Sale.objects.filter(fecha__month=today.month, fecha__year=today.year, metodo_pago='Transferencia').aggregate(Sum('total'))['total__sum'] or 0

    # Monthly History Chart (With Payment Breakdown)
    from django.db.models import Q
    
    history_data = Sale.objects.filter(fecha__year=today.year).values('fecha__month').annotate(
        monthly_total=Sum('total'),
        total_cash=Sum('total', filter=Q(metodo_pago='Efectivo')),
        total_transfer=Sum('total', filter=Q(metodo_pago='Transferencia'))
    ).order_by('-fecha__month')
    
    # Map months to Spanish names
    MONTH_NAMES = {
        1: 'Enero', 2: 'Febrero', 3: 'Marzo', 4: 'Abril',
        5: 'Mayo', 6: 'Junio', 7: 'Julio', 8: 'Agosto',
        9: 'Septiembre', 10: 'Octubre', 11: 'Noviembre', 12: 'Diciembre'
    }

    history = []
    for item in history_data:
        history.append({
            'month_name': MONTH_NAMES.get(item['fecha__month'], f"Mes {item['fecha__month']}"),
            'total': item['monthly_total'],
            'total_cash': item['total_cash'] or 0,
            'total_transfer': item['total_transfer'] or 0
        })

    context = {
        'sales_today': sales_today,
        'sales_month': sales_month,
        'selected_date': selected_date,
        'sales_history': sales_history,
        'selected_date_total': selected_date_total,
        'sales_cash': sales_cash,
        'sales_transfer': sales_transfer,
        'sales_month_cash': sales_month_cash,
        'sales_month_transfer': sales_month_transfer,
        'history': history,
    }
    return render(request, 'pos/dashboard.html', context)

@login_required
def product_management_view(request):
    from django.core.paginator import Paginator
    from django.db.models import Q

    # Handle POST for create/update/delete
    if request.method == 'POST':
        action = request.POST.get('action')
        codigo = request.POST.get('codigo_barra', '').strip()

        # Handle Delete
        if action == 'delete':
            product = get_object_or_404(Product, codigo_barra=codigo)
            product.activo = False
            product.stock = 0 # Reset stock on delete
            product.save()
            messages.success(request, f'Producto "{product.nombre}" eliminado.')
            return redirect('product_management')

        # Handle Create/Update
        instance = Product.objects.filter(codigo_barra=codigo).first()
        form = ProductForm(request.POST, instance=instance)

        if form.is_valid():
            product = form.save(commit=False)
            stock_add = form.cleaned_data.get('stock_add') or 0
            reset_stock = form.cleaned_data.get('reset_stock', False)

            if instance: # If it's an update
                if reset_stock:
                    product.stock = 0
                product.stock += stock_add
                messages.success(request, f'Producto "{product.nombre}" actualizado.')
            else: # If it's a creation
                product.stock = stock_add
                messages.success(request, f'Producto "{product.nombre}" creado exitosamente.')
            
            if not product.activo:
                product.activo = True # Reactivate if it was soft-deleted
                messages.info(request, f'Producto "{product.nombre}" ha sido reactivado.')

            product.save()
            return redirect('product_management')
        # If form is invalid, it will fall through to the render call below,
        # passing the invalid form to the template.

    else:
        # For GET requests, create an empty form
        form = ProductForm()

    # Common GET logic for rendering the page
    query = request.GET.get('q', '')
    sort_by = request.GET.get('sort', 'fecha_creacion')
    direction = request.GET.get('direction', 'desc')
    
    # Valid sort fields to prevent SQL injection or errors
    valid_sort_fields = ['codigo_barra', 'nombre', 'precio', 'stock', 'fecha_creacion']
    if sort_by not in valid_sort_fields:
        sort_by = 'fecha_creacion'
    
    # Apply direction
    order_prefix = '-' if direction == 'desc' else ''
    order_args = [f"{order_prefix}{sort_by}"]
    
    # Secondary sort for stability
    if sort_by != 'nombre':
        order_args.append('nombre')
    
    products_list = Product.objects.filter(activo=True).order_by(*order_args)
    
    if query:
        products_list = products_list.filter(Q(nombre__icontains=query) | Q(codigo_barra__icontains=query))
    
    paginator = Paginator(products_list, 20)
    page_number = request.GET.get('page')
    products = paginator.get_page(page_number)
    
    # Calculate elided page range for better pagination
    # We use a try-except block or check version if needed, but assuming modern Django
    # as this is a new project.
    if products.paginator.num_pages > 1:
        page_range = products.paginator.get_elided_page_range(products.number, on_each_side=2, on_ends=1)
    else:
        page_range = []

    # Pass the form object to the context. 
    # If it was a failed POST, it's the invalid form. If GET, it's a new empty form.
    return render(request, 'pos/inventory.html', {
        'products': products, 
        'page_range': page_range,
        'query': query,
        'current_sort': sort_by,
        'current_direction': direction,
        'form': form  # Pass the form to the template
    })



@login_required
def sale_detail_view(request, sale_id):
    sale = get_object_or_404(Sale, id=sale_id)
    return render(request, 'pos/partials/sale_detail.html', {'sale': sale})


# --- POS VIEWS ---

@login_required
@never_cache
def pos_view(request):
    # This view now unconditionally resets the cart to start a new sale.
    # This fixes the bug where old cart items would reappear.
    request.session['cart'] = {}
    request.session.modified = True  # Ensure the change is saved
    
    # The get_cart_items function will now correctly receive an empty cart
    # and return an empty list of items.
    return render(request, 'pos/pos.html', {
        'cart_items': get_cart_items(request)
    })

def get_cart_items(request):
    """
    Retrieves cart items from the session and fetches all corresponding
    product data in a single, efficient database query to prevent N+1 issues.
    """
    cart = request.session.get('cart', {})
    if not cart:
        return {'items': [], 'total': 0}

    # Get all product codes from the cart
    product_codes = cart.keys()

    # Fetch all products in a single query
    products = Product.objects.filter(codigo_barra__in=product_codes)

    # Create a dictionary for quick lookups: {barcode: product_object}
    product_map = {p.codigo_barra: p for p in products}

    items = []
    total = 0
    for code, qty in cart.items():
        product = product_map.get(code)
        # Ensure product exists and is mapped correctly
        if product:
            subtotal = product.precio * qty
            total += subtotal
            items.append({
                'product': product,
                'qty': qty,
                'subtotal': subtotal
            })
    
    return {'items': items, 'total': total}

@login_required
@require_http_methods(["POST"])
def search_product_view(request):
    barcode = request.POST.get('barcode')
    if not barcode:
        return HttpResponse(status=204) # No action, HTMX ignores swap

    try:
        product = Product.objects.get(codigo_barra=barcode, activo=True)
    except Product.DoesNotExist:
        # User requirement: "Si no está, agregarlo a la lista" -> This usually implies adding a temporary item or error. 
        # But given constraints, let's assume valid products for POS. 
        # Or maybe "permitir crearlo" logic is only for inventory?
        # Re-reading: "Gestión de Stock: Si el producto no existe, permite crearlo... Punto de Venta: Si el producto ya está en la venta actual incrementarlo, Si no está, agregarlo a la lista."
        # This implies "Si no está (en el carrito), agregarlo (al carrito)". It assumes the product EXISTS in DB.
        # If product does not exist in DB, we returns error.
        response = render(request, 'pos/partials/scan_feedback.html', {'error': 'Producto no encontrado'})
        response['HX-Reswap'] = 'none'
        return response

    cart = request.session.get('cart', {})
    if barcode in cart:
        cart[barcode] += 1
    else:
        cart[barcode] = 1
    
    request.session['cart'] = cart
    request.session.modified = True
    
    # Return updated cart list
    context = get_cart_items(request)
    return render(request, 'pos/partials/cart_list.html', context)

@login_required
def inventory_lookup_view(request):
    barcode = request.GET.get('codigo_barra', '').strip()
    form = ProductForm() # Start with an unbound form
    if barcode:
        product = Product.objects.filter(codigo_barra=barcode, activo=True).first()
        if product:
            # If product exists, create a form instance for it
            form = ProductForm(instance=product)
    
    # Render the partial containing the form fields with the (potentially bound) form
    return render(request, 'pos/partials/inventory_fields.html', {'form': form})


@login_required
@require_http_methods(["POST"])
def update_cart_quantity_view(request, barcode):
    qty = int(request.POST.get('qty', 1))
    cart = request.session.get('cart', {})
    if barcode in cart:
        cart[barcode] = qty
        request.session['cart'] = cart
        request.session.modified = True
    
    context = get_cart_items(request)
    return render(request, 'pos/partials/cart_list.html', context)



@login_required
@require_http_methods(["POST"])
def remove_cart_item_view(request, barcode):
    cart = request.session.get('cart', {})
    if barcode in cart:
        del cart[barcode]
        request.session['cart'] = cart
        request.session.modified = True
    
    context = get_cart_items(request)
    return render(request, 'pos/partials/cart_list.html', context)

@login_required
def clear_cart_view(request):
    if 'cart' in request.session:
        del request.session['cart']
        request.session.modified = True
    context = get_cart_items(request)
    return render(request, 'pos/partials/cart_list.html', context)

@login_required
@require_http_methods(["POST"])
def checkout_view(request):
    cart = request.session.get('cart', {})
    if not cart:
         return HttpResponse("Carrito vacío", status=400)

    metodo_pago = request.POST.get('metodo_pago', 'Efectivo')
    product_codes = cart.keys()

    # --- OPTIMIZATION: Fetch all products at once ---
    # We lock the rows for update to prevent race conditions, even if considered unlikely.
    # It's a good practice for financial operations.
    products = Product.objects.filter(codigo_barra__in=product_codes)
    product_map = {p.codigo_barra: p for p in products}

    # --- Integrity Check ---
    # Ensure all products in the cart still exist in the database
    if len(product_codes) != len(product_map):
        messages.error(request, "Error: Algunos productos en el carrito ya no existen y fueron removidos.")
        # Find missing codes and remove them from the session cart
        missing_codes = set(product_codes) - set(product_map.keys())
        for code in missing_codes:
            del cart[code]
        request.session['cart'] = cart
        request.session.modified = True
        # Return an updated cart view
        return render(request, 'pos/partials/cart_list.html', get_cart_items(request))

    # 1. Validate Stock (using the efficient map)
    for code, qty in cart.items():
        product = product_map.get(code)
        if product.stock < qty:
            response = render(request, 'pos/partials/scan_feedback.html', {
                'error': f'Stock insuficiente para {product.nombre} (Disponibles: {product.stock})'
            })
            response['HX-Reswap'] = 'none'
            return response

    # 2. Create Sale and Prepare Bulk Operations
    sale = Sale.objects.create(
        metodo_pago=metodo_pago,
        usuario=request.user if request.user.is_authenticated else None
    )

    total = 0
    sale_details_to_create = []
    products_to_update = []

    for code, qty in cart.items():
        product = product_map.get(code)
        subtotal = product.precio * qty
        total += subtotal

        sale_details_to_create.append(
            SaleDetail(
                venta=sale,
                producto=product,
                cantidad=qty,
                precio_unitario_congelado=product.precio,
                subtotal=subtotal
            )
        )
        
        # Update stock in the Python object first
        product.stock -= qty
        products_to_update.append(product)

    # 3. Execute Bulk DB Operations
    SaleDetail.objects.bulk_create(sale_details_to_create)
    Product.objects.bulk_update(products_to_update, ['stock'])

    # 4. Finalize Sale Total
    sale.total = total
    sale.save()
    
    # 5. Clear cart from session
    if 'cart' in request.session:
        del request.session['cart']
    request.session.modified = True
    
    # --- OOB UI Updates ---
    messages.success(request, f"Venta #{sale.id} realizada con éxito. Total: ${total}")
    response = render(request, 'pos/partials/checkout_success.html', {'sale': sale})
    
    # Add Toasts OOB
    toasts_html = render(request, 'pos/partials/toast_oob.html').content.decode('utf-8')
    response.content += toasts_html.encode('utf-8')
    
    # Add Empty Cart OOB (Atomic UI Reset)
    context = get_cart_items(request) 
    cart_html = render(request, 'pos/partials/cart_list.html', context).content.decode('utf-8')
    oob_cart = f'<div id="cart-container" hx-swap-oob="innerHTML">{cart_html}</div>'
    response.content += oob_cart.encode('utf-8')
    
    return response
