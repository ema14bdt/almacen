from django import forms
from .models import Product

class ProductForm(forms.ModelForm):
    """
    Formulario para la creación y actualización de Productos.
    El campo 'stock_add' no es parte del modelo, se usa para manejar
    la lógica de añadir stock en la vista.
    """
    stock_add = forms.IntegerField(
        label="Añadir Stock",
        required=False,
        min_value=0,
        initial=0,
        help_text="Cantidad para AÑADIR al stock actual. Si es un producto nuevo, será el stock inicial.",
        widget=forms.NumberInput(attrs={'class': 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'})
    )
    
    reset_stock = forms.BooleanField(
        label="Vaciar Stock (0)",
        required=False,
        help_text="Si se marca, el stock actual pasará a 0 antes de sumar la nueva cantidad.",
        widget=forms.CheckboxInput(attrs={'class': 'rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 mr-2'})
    )

    class Meta:
        model = Product
        fields = ['codigo_barra', 'nombre', 'precio']
        # 'stock' y 'activo' se manejarán manualmente en la vista.
        widgets = {
            'codigo_barra': forms.TextInput(attrs={
                'class': 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border',
                'placeholder': 'Escanear o escribir...'
            }),
            'nombre': forms.TextInput(attrs={'class': 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border'}),
            'precio': forms.NumberInput(attrs={'class': 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border', 'step': '0.01'}),
        }
        labels = {
            'codigo_barra': 'Código de barra',
        }

    def __init__(self, *args, **kwargs):
        super().__init__(*args, **kwargs)
        # Si el formulario es para una instancia existente (actualización),
        # deshabilitamos el campo 'codigo_barra' para que no sea editable.
        if self.instance and self.instance.pk:
            self.fields['codigo_barra'].disabled = True
