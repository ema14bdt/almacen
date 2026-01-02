from django import template
from django.utils.safestring import mark_safe

register = template.Library()

@register.filter
def currency_fmt(value):
    """
    Formats a number as currency with custom styling:
    $1.234,56 where decimals are smaller/lighter.
    """
    try:
        value = float(value)
    except (ValueError, TypeError):
        return value

    # Validar si es negativo para manejar el signo menos por fuera si se desea, 
    # pero el standard string formatting lo maneja.
    
    # Format number with commas for thousands and dot for decimal (standard US, temporary)
    formatted = "{:,.2f}".format(value)
    
    # Swap separators: comma -> temp, dot -> comma, temp -> dot
    # 1,234.56 -> 1.234,56
    formatted = formatted.replace(",", "X").replace(".", ",").replace("X", ".")
    
    parts = formatted.split(',')
    integer_part = parts[0]
    decimal_part = parts[1] if len(parts) > 1 else "00"
    
    html = f'${integer_part},<span class="text-gray-400 text-xs">{decimal_part}</span>'
    return mark_safe(html)
