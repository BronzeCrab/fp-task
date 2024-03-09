## How to start project:

1. Run mysql docker container:

```bash

sudo docker compose up -d

```
2. Find php ini file:

```bash
php -i | grep "php.ini"
```

3. Enable php mysqli extension:

```bash
extension=mysqli
```

4. Verify that this extension is enabled:

```bash
php -m | grep mysqli
```

5. Run the project:

```bash
php -f test.php
```