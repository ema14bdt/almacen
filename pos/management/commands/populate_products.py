from django.core.management.base import BaseCommand
from pos.models import Product
import random

class Command(BaseCommand):
    help = 'Populates the database with sample products'

    def handle(self, *args, **kwargs):
        products = [
            ('7791234567890', 'Coca Cola 2.25L', 2500.00),
            ('7799876543210', 'Galletitas Oreo', 1200.50),
            ('7791111111111', 'Leche La Serenísima', 1500.00),
            ('7792222222222', 'Pan Bimbo Artesano', 3200.00),
            ('123456', 'Producto Test Corto', 500.00),
        ]

        count = 0
        for code, name, price in products:
            obj, created = Product.objects.get_or_create(
                codigo_barra=code,
                defaults={'nombre': name, 'precio': price, 'stock': 100}
            )
            if created:
                count += 1
                self.stdout.write(self.style.SUCCESS(f'Created {name}'))
            else:
                self.stdout.write(f'Skipped {name} (exists)')
        
        self.stdout.write(self.style.SUCCESS(f'Successfully populated {count} products'))
