from django.core.management.base import BaseCommand
from pos.models import Product
import random

class Command(BaseCommand):
    help = 'Populates the database with sample products'

    def handle(self, *args, **kwargs):
        products = [
            # Bebidas
            ('7791234567890', 'Coca Cola 2.25L', 2500.00),
            ('7790895000997', 'Sprite 2.25L', 2500.00),
            ('7790895000452', 'Fanta Naranja 2.25L', 2500.00),
            ('7792798000034', 'Agua Villa del Sur 2L', 1100.00),
            ('7790199000123', 'Cerveza Quilmes 473ml', 1350.00),
            ('7790070411234', 'Cerveza Brahma 473ml', 1300.00),
            ('7790267000222', 'Fernet Branca 750ml', 9800.00),
            ('7791234560001', 'Vino Malbec Rutini 750ml', 8500.00),
            ('7791234560002', 'Jugo Tang Naranja', 450.00),
            ('7791234560003', 'Agua Saborizada Levite 1.5L', 1600.00),
            ('7791234560004', 'Gatorade Manzana 500ml', 1200.00),

            # Almacén
            ('7792222222222', 'Pan Bimbo Artesano 500g', 3200.00),
            ('7790040100234', 'Arroz Gallo Oro 1kg', 2100.00),
            ('7790040100555', 'Fideos Matarazzo Spaghetti 500g', 1600.00),
            ('7790040100666', 'Fideos Lucchetti Tirabuzón 500g', 1300.00),
            ('7790250000111', 'Aceite Natura Girasol 1.5L', 2900.00),
            ('7790250000222', 'Aceite Cocinero Mezcla 900ml', 1800.00),
            ('7790070000111', 'Puré de Tomate Filetto 520g', 950.00),
            ('7790070000222', 'Harina Pureza 0000 1kg', 1400.00),
            ('7790070000333', 'Harina Blancaflor Leudante 1kg', 1700.00),
            ('7790060000111', 'Yerba Playadito 1kg', 4600.00),
            ('7790060000222', 'Yerba Taragüí 500g', 2400.00),
            ('7790060000333', 'Azúcar Ledesma 1kg', 1250.00),
            ('7790060000444', 'Café Dolca Instantáneo 170g', 5500.00),
            ('7790060000555', 'Té Taragüí 50 saquitos', 1900.00),
            ('7790060000666', 'Mermelada BC Durazno 390g', 2800.00),
            ('7790060000777', 'Dulce de Leche La Serenísima 400g', 2900.00),
            ('7790060000888', 'Sal Dos Anclas Fina 500g', 1100.00),
            ('7790060000999', 'Mayonesa Natura 500cc', 2100.00),
            ('7790060001000', 'Ketchup Hellmanns 250g', 1800.00),
            ('7790060001111', 'Atún La Campagnola Aceite 170g', 3500.00),
            ('7790060001222', 'Arvejas Noel Lata 300g', 900.00),

            # Galletitas y Snacks
            ('7799876543210', 'Galletitas Oreo 117g', 1200.50),
            ('7790040000111', 'Galletitas Chocolinas 170g', 1500.00),
            ('7790040000222', 'Galletitas Criollitas Pack 3', 2200.00),
            ('7790040000333', 'Galletitas Rumba 112g', 1100.00),
            ('7790040000444', 'Papas Lays Clásicas 85g', 2300.00),
            ('7790040000555', 'Doritos Queso 90g', 2500.00),
            ('7790040000666', 'Alfajor Jorgito Chocolate', 800.00),
            ('7790040000777', 'Alfajor Guaymallén Dulce de Leche', 400.00),

            # Lácteos y Frescos
            ('7791111111111', 'Leche La Serenísima 1L', 1500.00),
            ('7793333333333', 'Manteca La Serenísima 200g', 3100.00),
            ('7794444444444', 'Casancrem Clásico 300g', 3800.00),
            ('7795555555555', 'Yogur Bebible Ser Frutilla 1kg', 2100.00),
            ('7796666666666', 'Queso Por Salut La Paulina 1kg', 9500.00),
            ('7797777777777', 'Tapas de Empanadas La Salteña x12', 1700.00),
            ('7798888888888', 'Salchichas Vienissima x6', 1600.00),
            ('7799999999999', 'Hamburguesas Paty x4', 4500.00),

            # Limpieza
            ('7790111000111', 'Lavandina Ayudín 1L', 1400.00),
            ('7790111000222', 'Detergente Magistral Limón 500ml', 2500.00),
            ('7790111000333', 'Jabón Líquido Ala 3L', 9500.00),
            ('7790111000444', 'Suavizante Vivere 900ml', 3200.00),
            ('7790111000555', 'Papel Higiénico Higienol 4u', 3500.00),
            ('7790111000666', 'Rollos de Cocina Sussex x3', 2800.00),
            ('7790111000777', 'Desodorante de Ambiente Glade', 2100.00),
            ('7790111000888', 'Esponja Mortimer Multiuso', 800.00),

            # Perfumería
            ('7790222000111', 'Shampoo Pantene 400ml', 5200.00),
            ('7790222000222', 'Acondicionador Dove 400ml', 5100.00),
            ('7790222000333', 'Jabón de Tocador Rexona x3', 2300.00),
            ('7790222000444', 'Pasta Dental Colgate Total 90g', 3500.00),
            ('7790222000555', 'Desodorante Axe Body Spray', 3100.00),
            ('7790222000666', 'Toallitas Femeninas Siempre Libre', 2800.00)
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
