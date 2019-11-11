<?php
declare(strict_types=1);

namespace Pandawa\Module\Api\Routing\Loader;

use InvalidArgumentException;
use Pandawa\Component\Presenter\PresenterRegistryInterface;
use Pandawa\Module\Api\Http\Controller\PresenterHandlerInterface;
use Route;
use RuntimeException;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class PresenterLoader extends AbstractLoader
{
    /**
     * @var string
     */
    private $presenterHandlerClass;

    /**
     * @var PresenterRegistryInterface
     */
    private $presenterRegistry;

    /**
     * Constructor.
     *
     * @param string                     $presenterHandlerClass
     * @param PresenterRegistryInterface $presenterRegistry
     */
    public function __construct(string $presenterHandlerClass, PresenterRegistryInterface $presenterRegistry)
    {
        if (!in_array(PresenterHandlerInterface::class, class_implements($presenterHandlerClass))) {
            throw new RuntimeException(
                sprintf(
                    'Presenter handler "%s" should implement "%s"',
                    $presenterHandlerClass,
                    PresenterHandlerInterface::class
                )
            );
        }

        $this->presenterHandlerClass = $presenterHandlerClass;
        $this->presenterRegistry = $presenterRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function support(string $type): bool
    {
        return 'presenter' === $type;
    }

    /**
     * {@inheritdoc}
     */
    protected function createRoutes(string $type, string $path, string $controller, array $route, array $parent = []): array
    {
        if (null === $presenter = array_get($route, 'presenter')) {
            throw new InvalidArgumentException('Route with type "presenter" should has presenter class.');
        }

        if (null === $this->presenterRegistry) {
            throw new RuntimeException('There are no presenter registry detected.');
        }

        if (!$this->presenterRegistry->has($presenter)) {
            throw new RuntimeException(sprintf('Presenter "%s" is not registered.', $presenter));
        }

        $routeObject = Route::match((array)array_get($route, 'methods', 'get'), $path, $controller);

        if ($parentRouteName = $parent['name'] ?? null) {
            $routeObject->name($parentRouteName);
        }

        return [$routeObject];
    }

    /**
     * {@inheritdoc}
     */
    protected function getRouteDefaultParameters(array $route): array
    {
        return [
            'presenter' => array_get($route, 'presenter'),
            'name'      => $this->getName($route),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getController(array $route): string
    {
        return $this->presenterHandlerClass;
    }

    /**
     * @param array $route
     *
     * @return string
     */
    private function getName(array $route): string
    {
        if ($routeName = $route['name'] ?? null) {
            return (string)$routeName;
        }

        $presenter = $route['presenter'] ?? '';

        return substr($presenter, strrpos($presenter, ':') + 1);
    }
}
