.PHONY: test-unit test-integration test-coverage analyse check-style fix-style

# запуск Unit тестов
test-unit:
	XDEBUG_MODE=coverage vendor/bin/phpunit --testsuite=Unit || true

# запуск Integration тестов
test-integration:
	XDEBUG_MODE=coverage vendor/bin/phpunit --testsuite=Integration || true

# посмотреть процент покрытия тестами
test-coverage:
	XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text --no-coverage || true

# запуск PHPStan
analyse:
	vendor/bin/phpstan analyse

# запуск PHPCS
check-style:
	vendor/bin/phpcs

# автоматическое исправление стиля кода по PSR-12
fix-style:
	vendor/bin/phpcbf