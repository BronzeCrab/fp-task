## What I've done:

I've added docker-compose file just in case.

All tests are passed, also I added some my tests in func `additionalTestBuildQuery`.

## How to start this task:

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