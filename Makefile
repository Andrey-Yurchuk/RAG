.PHONY: test-unit test-integration test-coverage

# запуск Unit тестов
test-unit:
	XDEBUG_MODE=coverage vendor/bin/phpunit --testsuite=Unit || true

# запуск Integration тестов
test-integration:
	XDEBUG_MODE=coverage vendor/bin/phpunit --testsuite=Integration || true

# посмотреть процент покрытия тестами
test-coverage:
	XDEBUG_MODE=coverage vendor/bin/phpunit --coverage-text --no-coverage || true