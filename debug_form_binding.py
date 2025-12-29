import os
import django
import sys

# Setup Django environment
sys.path.append('/home/ema14bdt/Documentos/personal/repositorios/almacen')
os.environ.setdefault('DJANGO_SETTINGS_MODULE', 'almacen.settings')
django.setup()

from pos.models import Product
from pos.forms import ProductForm

def test_binding():
    print("--- Testing Product Binding ---")
    # Get any active product
    product = Product.objects.filter(activo=True).first()
    if not product:
        print("No active products found to test.")
        return

    print(f"Testing with Product: {product.nombre} (Code: {product.codigo_barra})")
    
    # Simulate View Logic
    barcode = product.codigo_barra
    # Lookup
    found_product = Product.objects.filter(codigo_barra=barcode, activo=True).first()
    
    if found_product:
        print("Product found in DB query.")
        form = ProductForm(instance=found_product)
        print(f"Form Bound: {form.is_bound}") # False is expected for instance-only form (unbound but with initial)
        print(f"Form Initial Name: {form.initial.get('nombre')}")
        print(f"Form Initial Price: {form.initial.get('precio')}")
        
        # Check rendered HTML for value
        rendered = str(form['nombre'])
        if f'value="{product.nombre}"' in rendered:
            print("SUCCESS: Input contains correct value.")
        else:
            print("FAILURE: Input missing value attribute.")
            print("Rendered Input:", rendered)
    else:
        print("Product NOT found using filter.")

if __name__ == "__main__":
    test_binding()
