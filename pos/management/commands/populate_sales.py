from django.core.management.base import BaseCommand
from pos.models import Product, Sale, SaleDetail, CashRegisterSession
from django.contrib.auth.models import User
import random
from django.utils import timezone
from datetime import timedelta

class Command(BaseCommand):
    help = 'Populates the database with 60 sample sales from the last year'

    def handle(self, *args, **kwargs):
        # Ensure we have products and a user
        products = list(Product.objects.filter(activo=True))
        if not products:
            self.stdout.write(self.style.ERROR('No products found. Run populate_products first.'))
            return

        user = User.objects.first()
        if not user:
            # Create a mock user if none exists
            user = User.objects.create_user('testadmin', 'admin@test.com', 'admin123')
            self.stdout.write(self.style.SUCCESS('Created test user "testadmin"'))

        # Create sessions for past months to avoid validation errors if we enforce it strictly in views (though logic is only in checkout view)
        # But for correctness, let's just create sales. The checkout view validation only blocks New sales creation via UI.
        # Direct DB creation bypasses that check.
        
        end_date = timezone.now()
        start_date = end_date - timedelta(days=365)
        
        created_count = 0

        for i in range(60):
            # Random date within last year
            # days_delta = random.randint(0, 365)
            sale_date = timezone.now()
            
            # Determine payment method
            metodo = random.choice(['Efectivo', 'Transferencia', 'Efectivo', 'Efectivo']) # Weighted towards cash

            # Create Sale
            sale = Sale.objects.create(
                fecha=sale_date,
                metodo_pago=metodo,
                usuario=user,
                total=0 # Will update later
            )
            
            # Force the date (auto_now_add might override it otherwise, but date is default=timezone.now, so we can override)
            # Actually Sale.fecha is default=timezone.now, not auto_now_add, so we are good.
            # But let's be sure
            sale.fecha = sale_date
            sale.save()

            # Add random products
            num_items = random.randint(1, 5)
            # Pick random unique products
            selected_products = random.sample(products, min(num_items, len(products)))
            
            sale_total = 0
            
            for product in selected_products:
                qty = random.randint(1, 3)
                price = product.precio
                subtotal = price * qty
                sale_total += subtotal
                
                SaleDetail.objects.create(
                    venta=sale,
                    producto=product,
                    cantidad=qty,
                    precio_unitario_congelado=price,
                    subtotal=subtotal
                )
            
            sale.total = sale_total
            sale.save()
            created_count += 1

        self.stdout.write(self.style.SUCCESS(f'Successfully created {created_count} sales over the last year.'))
