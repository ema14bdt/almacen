from django.contrib import admin
from .models import Product, Sale, SaleDetail

@admin.register(Product)
class ProductAdmin(admin.ModelAdmin):
    list_display = ('codigo_barra', 'nombre', 'precio', 'stock', 'activo', 'fecha_creacion')
    search_fields = ('codigo_barra', 'nombre')
    list_filter = ('activo', 'fecha_creacion')
    ordering = ('-fecha_creacion',)

class SaleDetailInline(admin.TabularInline):
    model = SaleDetail
    extra = 0
    readonly_fields = ('subtotal',)

@admin.register(Sale)
class SaleAdmin(admin.ModelAdmin):
    list_display = ('id', 'fecha', 'total', 'metodo_pago', 'usuario')
    list_filter = ('fecha', 'metodo_pago')
    search_fields = ('id',)
    inlines = [SaleDetailInline]
    readonly_fields = ('fecha', 'total')

@admin.register(SaleDetail)
class SaleDetailAdmin(admin.ModelAdmin):
    list_display = ('id', 'venta', 'producto', 'cantidad', 'subtotal')
    list_filter = ('venta__fecha',)
    search_fields = ('venta__id', 'producto__nombre')
