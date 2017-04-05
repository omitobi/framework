<?php

namespace Arc\Http;

use Arc\BasePlugin;
use Arc\Exceptions\Handler;
use Illuminate\Contracts\Http\Kernel as KernelContract;
use Illuminate\Pipeline\Pipeline;

class Kernel implements KernelContract
{
    /**
     * The application implementation.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The router instance.
     *
     * @var \Illuminate\Routing\Router
     */
    protected $router;

    /**
     * The bootstrap classes for the application.
     *
     * @var array
     */
    protected $bootstrappers = [
    ];

    /**
     * The application's middleware stack.
     *
     * @var array
     */
    protected $middleware = [];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [];

    public function __construct(BasePlugin $plugin, Router $router)
    {
        $this->app = $plugin;
        $this->router = $router;
        foreach ($this->routeMiddleware as $key => $middleware) {
            $router->middleware($key, $middleware);
        }
    }

    public function bootstrap()
    {

    }

    public function getApplication()
    {
        return $this->app;
    }

    /**
     * Handle the request and return a response
     * @param $request
     * @return Illuminate\Http\Response
     **/
    public function handle($request)
    {
        try {
            $request->enableHttpMethodParameterOverride();
            $response = $this->sendRequestThroughRouter($request);
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            $response = new DeferToWordpress;
        } catch (\Exception $e) {
            $this->reportException($e);
            $response = $this->renderException($request, $e);
        } catch (Throwable $e) {
            $e = new FatalThrowableError($e);
            $this->reportException($e);
            $response = $this->renderException($request, $e);
        }

        return $response;
    }

    public function terminate($request, $response)
    {
        if (method_exists($response, 'shouldBeHandledByWordpress')) {
            if ($response->shouldBeHandledByWordpress()) {
                return;
            }
        }

        $middlewares = $this->app->shouldSkipMiddleware() ? [] : array_merge(
            $this->gatherRouteMiddlewares($request),
            $this->middleware
        );
        foreach ($middlewares as $middleware) {
            list($name, $parameters) = $this->parseMiddleware($middleware);
            $instance = $this->app->make($name);
            if (method_exists($instance, 'terminate')) {
                $instance->terminate($request, $response);
            }
        }
        $this->app->terminate();
    }

    /**
     * Send the given request through the middleware / router.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    protected function sendRequestThroughRouter($request)
    {
        $this->app->instance('request', $request);
        $this->app->instance(Request::class, $request);
        $this->bootstrap();
        return (new Pipeline($this->app))
                    ->send($request)
                    ->through($this->app->shouldSkipMiddleware() ? [] : $this->middleware)
                    ->then($this->dispatchToRouter());
    }

     /**
     * Get the route dispatcher callback.
     *
     * @return \Closure
     */
    protected function dispatchToRouter()
    {
        return function ($request) {
            $this->app->instance('request', $request);

            return $this->router->dispatch($request);
        };
    }

    public function gatherRouteMiddlewares()
    {
        return $this->routeMiddleware;
    }

    public function renderException($request, \Exception $e)
    {
        $this->app->make(Handler::class)->render($request, $e);
    }

    public function reportException(\Exception $e)
    {
        $this->app->make(Handler::class)->report($e);
    }
}