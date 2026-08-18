<?php
/**
 * @package DNorteCore\Tests
 */

declare(strict_types=1);

namespace DNorteCore\Tests\Unit\Container;

use DNorteCore\Container\Container;
use DNorteCore\Container\Exceptions\UnresolvableParameterException;
use DNorteCore\Tests\Unit\TestCase;
use stdClass;

final class ContainerTest extends TestCase {

	public function test_make_autowires_a_class_without_constructor(): void {
		$container = new Container();

		$instance = $container->make( NoConstructorFixture::class );

		self::assertInstanceOf( NoConstructorFixture::class, $instance );
	}

	public function test_make_autowires_nested_class_dependencies_recursively(): void {
		$container = new Container();

		$instance = $container->make( DependsOnFixture::class );

		self::assertInstanceOf( DependsOnFixture::class, $instance );
		self::assertInstanceOf( NoConstructorFixture::class, $instance->dependency );
	}

	public function test_make_uses_default_scalar_values_when_no_binding_given(): void {
		$container = new Container();

		$instance = $container->make( ScalarDefaultFixture::class );

		self::assertSame( 'fallback', $instance->label );
	}

	public function test_make_throws_when_a_scalar_parameter_cannot_be_resolved(): void {
		$container = new Container();

		$this->expectException( UnresolvableParameterException::class );

		$container->make( RequiredScalarFixture::class );
	}

	public function test_make_accepts_explicit_parameters_overriding_autowiring(): void {
		$container = new Container();

		$instance = $container->make( RequiredScalarFixture::class, array( 'label' => 'explicit' ) );

		self::assertSame( 'explicit', $instance->label );
	}

	public function test_bind_resolves_via_factory_callable_on_every_call(): void {
		$container = new Container();
		$calls     = 0;

		$container->bind(
			stdClass::class,
			static function () use ( &$calls ): stdClass {
				$calls++;
				$object       = new stdClass();
				$object->call = $calls;

				return $object;
			}
		);

		$first  = $container->make( stdClass::class );
		$second = $container->make( stdClass::class );

		self::assertSame( 1, $first->call );
		self::assertSame( 2, $second->call );
		self::assertNotSame( $first, $second );
	}

	public function test_singleton_resolves_the_same_instance_on_every_call(): void {
		$container = new Container();

		$container->singleton( stdClass::class, static fn () => new stdClass() );

		$first  = $container->make( stdClass::class );
		$second = $container->make( stdClass::class );

		self::assertSame( $first, $second );
	}

	public function test_instance_registers_an_already_built_object(): void {
		$container = new Container();
		$object    = new stdClass();

		$container->instance( stdClass::class, $object );

		self::assertSame( $object, $container->get( stdClass::class ) );
	}

	public function test_has_reflects_bindings_instances_and_existing_classes(): void {
		$container = new Container();

		self::assertFalse( $container->has( 'Nonexistent\\Class' ) );
		self::assertTrue( $container->has( stdClass::class ) );

		$container->instance( 'foo', new stdClass() );
		self::assertTrue( $container->has( 'foo' ) );
	}

	public function test_make_throws_for_a_class_that_does_not_exist_and_has_no_binding(): void {
		$container = new Container();

		$this->expectException( UnresolvableParameterException::class );

		$container->make( 'Totally\\Missing\\ClassName' );
	}

	public function test_make_throws_for_a_non_instantiable_abstract_without_binding(): void {
		$container = new Container();

		$this->expectException( UnresolvableParameterException::class );

		$container->make( AbstractFixture::class );
	}
}

final class NoConstructorFixture {

}

final class DependsOnFixture {

	public function __construct( public readonly NoConstructorFixture $dependency ) {
	}
}

final class ScalarDefaultFixture {

	public function __construct( public readonly string $label = 'fallback' ) {
	}
}

final class RequiredScalarFixture {

	public function __construct( public readonly string $label ) {
	}
}

abstract class AbstractFixture {

}
