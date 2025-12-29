from django.urls import path
from . import views

urlpatterns = [
    # Root redirects to Dashboard (which redirects to login if needed)
    path('', views.dashboard_view, name='index'),

    # Dashboard
    path('dashboard/', views.dashboard_view, name='dashboard'),
    path('inventory/', views.product_management_view, name='product_management'),
    path('inventory/lookup/', views.inventory_lookup_view, name='product_lookup'),
    path('sale-detail/<int:sale_id>/', views.sale_detail_view, name='sale_detail'),
    
    # POS
    path('pos/', views.pos_view, name='pos'),
    path('pos/scan/', views.search_product_view, name='pos_scan'),
    path('pos/update/<str:barcode>/', views.update_cart_quantity_view, name='pos_update_cart'),
    path('pos/remove/<str:barcode>/', views.remove_cart_item_view, name='pos_remove_item'),
    path('pos/clear/', views.clear_cart_view, name='pos_clear_cart'),
    path('pos/checkout/', views.checkout_view, name='pos_checkout'),
]
