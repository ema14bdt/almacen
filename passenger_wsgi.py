import sys, os

# Specify the path to your Django project (where manage.py is)
sys.path.append(os.getcwd())

# Point to your project's settings
os.environ['DJANGO_SETTINGS_MODULE'] = 'config.settings'

from django.core.wsgi import get_wsgi_application
application = get_wsgi_application()
