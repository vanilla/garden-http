[global]

[travis]
user = {USER}
group = {USER}
listen = {SOCKET}
listen.mode = 0666
pm = static
pm.max_children = 5
php_admin_value[memory_limit] = 32M
