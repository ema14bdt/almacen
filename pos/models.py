from django.db import models
from django.contrib.auth.models import User
from django.utils import timezone

class Product(models.Model):
    codigo_barra = models.CharField(max_length=50, primary_key=True, help_text="Código de barras único del producto")
    nombre = models.CharField(max_length=200)
    precio = models.DecimalField(max_digits=10, decimal_places=2)
    stock = models.IntegerField(default=0)
    fecha_creacion = models.DateTimeField(auto_now_add=True)
    activo = models.BooleanField(default=True)

    def __str__(self):
        return f"{self.nombre} ({self.codigo_barra})"

    class Meta:
        indexes = [
            models.Index(fields=['nombre']),
        ]

class Sale(models.Model):
    METODO_PAGO_CHOICES = [
        ('Efectivo', 'Efectivo'),
        ('Transferencia', 'Transferencia'),
    ]

    fecha = models.DateTimeField(default=timezone.now)
    total = models.DecimalField(max_digits=12, decimal_places=2, default=0)
    metodo_pago = models.CharField(max_length=20, choices=METODO_PAGO_CHOICES)
    usuario = models.ForeignKey(User, on_delete=models.SET_NULL, null=True, blank=True)

    def __str__(self):
        return f"Venta #{self.id} - {self.fecha.strftime('%Y-%m-%d %H:%M')}"

class SaleDetail(models.Model):
    venta = models.ForeignKey(Sale, related_name='detalles', on_delete=models.CASCADE)
    producto = models.ForeignKey(Product, on_delete=models.PROTECT) # Prevent deleting product if sold
    cantidad = models.IntegerField(default=1)
    precio_unitario_congelado = models.DecimalField(max_digits=10, decimal_places=2)
    subtotal = models.DecimalField(max_digits=10, decimal_places=2)

    def save(self, *args, **kwargs):
        if not self.subtotal:
            self.subtotal = self.cantidad * self.precio_unitario_congelado
        super().save(*args, **kwargs)

    def __str__(self):
        return f"{self.cantidad}x {self.producto.nombre} en Venta #{self.venta.id}"

class CashRegisterSession(models.Model):
    usuario = models.ForeignKey(User, on_delete=models.CASCADE)
    fecha_apertura = models.DateTimeField(default=timezone.now)
    fecha_cierre = models.DateTimeField(null=True, blank=True)
    monto_apertura = models.DecimalField(max_digits=12, decimal_places=2)
    monto_cierre = models.DecimalField(max_digits=12, decimal_places=2, null=True, blank=True)

    @property
    def activa(self):
        return self.fecha_cierre is None

    def __str__(self):
        status = "Abierta" if self.activa else "Cerrada"
        return f"Caja {status} - {self.usuario.username} - {self.fecha_apertura.strftime('%Y-%m-%d %H:%M')}"
