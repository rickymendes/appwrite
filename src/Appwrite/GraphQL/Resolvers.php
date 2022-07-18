<?php

namespace Appwrite\GraphQL;

use Appwrite\GraphQL\Promises\CoroutinePromise;
use Appwrite\Utopia\Request;
use Appwrite\Utopia\Response;
use Utopia\App;
use Utopia\Database\Database;
use Utopia\Exception;
use Utopia\Route;

class Resolvers
{
    /**
     * Create a resolver for a given {@see Route}.
     *
     * @param App $utopia
     * @param ?Route $route
     * @return callable
     */
    public static function resolveAPIRequest(
        App $utopia,
        ?Route $route,
    ): callable {
        return fn($type, $args, $context, $info) => new CoroutinePromise(
            function (callable $resolve, callable $reject) use ($utopia, $route, $args, $context, $info) {
                $utopia = $utopia->getResource('current', true);
                $request = $utopia->getResource('request', true);
                $response = $utopia->getResource('response', true);
                $swoole = $request->getSwoole();

                $path = $route->getPath();
                foreach ($args as $key => $value) {
                    $path = \str_replace(':' . $key, $value, $path);
                }

                $swoole->server['request_method'] = $route->getMethod();
                $swoole->server['request_uri'] = $path;
                $swoole->server['path_info'] = $path;

                switch ($route->getMethod()) {
                    case 'GET':
                        $swoole->get = $args;
                        break;
                    default:
                        $swoole->post = $args;
                        break;
                }

                self::resolve($utopia, $request, $response, $resolve, $reject);
            }
        );
    }

    /**
     * Create a resolver for getting a document in a specified database and collection.
     *
     * @param App $utopia
     * @param Database $dbForProject
     * @param string $databaseId
     * @param string $collectionId
     * @return callable
     */
    public static function resolveDocumentGet(
        App $utopia,
        Database $dbForProject,
        string $databaseId,
        string $collectionId
    ): callable {
        return fn($type, $args, $context, $info) => new CoroutinePromise(
            function (callable $resolve, callable $reject) use ($utopia, $dbForProject, $databaseId, $collectionId, $type, $args) {
                $utopia = $utopia->getResource('current', true);
                $request = $utopia->getResource('request', true);
                $response = $utopia->getResource('response', true);
                $swoole = $request->getSwoole();
                $swoole->post = [
                    'databaseId' => $databaseId,
                    'collectionId' => $collectionId,
                    'documentId' => $args['id'],
                ];
                $swoole->server['request_method'] = 'GET';
                $swoole->server['request_uri'] = "/v1/databases/$databaseId/collections/$collectionId/documents/{$args['id']}";
                $swoole->server['path_info'] = "/v1/databases/$databaseId/collections/$collectionId/documents/{$args['id']}";

                self::resolve($utopia, $request, $response, $resolve, $reject);
            }
        );
    }

    /**
     * Create a resolver for listing documents in a specified database and collection.
     *
     * @param App $utopia
     * @param Database $dbForProject
     * @param string $databaseId
     * @param string $collectionId
     * @return callable
     */
    public static function resolveDocumentList(
        App $utopia,
        Database $dbForProject,
        string $databaseId,
        string $collectionId,
    ): callable {
        return fn($type, $args, $context, $info) => new CoroutinePromise(
            function (callable $resolve, callable $reject) use ($utopia, $dbForProject, $databaseId, $collectionId, $type, $args) {
                $utopia = $utopia->getResource('current', true);
                $request = $utopia->getResource('request', true);
                $response = $utopia->getResource('response', true);
                $swoole = $request->getSwoole();
                $swoole->post = [
                    'databaseId' => $databaseId,
                    'collectionId' => $collectionId,
                    'limit' => $args['limit'],
                    'offset' => $args['offset'],
                    'cursor' => $args['cursor'],
                    'cursorDirection' => $args['cursorDirection'],
                    'orderAttributes' => $args['orderAttributes'],
                    'orderType' => $args['orderType'],
                ];
                $swoole->server['request_method'] = 'GET';
                $swoole->server['request_uri'] = "/v1/databases/$databaseId/collections/$collectionId/documents";
                $swoole->server['path_info'] = "/v1/databases/$databaseId/collections/$collectionId/documents";

                self::resolve($utopia, $request, $response, $resolve, $reject);
            }
        );
    }

    /**
     * Create a resolver for mutating a document in a specified database and collection.
     *
     * @param App $utopia
     * @param Database $dbForProject
     * @param string $databaseId
     * @param string $collectionId
     * @param string $method
     * @return callable
     */
    public static function resolveDocumentMutate(
        App $utopia,
        Database $dbForProject,
        string $databaseId,
        string $collectionId,
        string $method,
    ): callable {
        return fn($type, $args, $context, $info) => new CoroutinePromise(
            function (callable $resolve, callable $reject) use ($utopia, $dbForProject, $databaseId, $collectionId, $method, $type, $args) {
                $utopia = $utopia->getResource('current', true);
                $request = $utopia->getResource('request', true);
                $response = $utopia->getResource('response', true);
                $swoole = $request->getSwoole();

                $id = $args['id'] ?? 'unique()';
                $read = $args['read'];
                $write = $args['write'];

                unset($args['id']);
                unset($args['read']);
                unset($args['write']);

                // Order must be the same as the route params
                $swoole->post = [
                    'databaseId' => $databaseId,
                    'documentId' => $id,
                    'collectionId' => $collectionId,
                    'data' => $args,
                    'read' => $read,
                    'write' => $write,
                ];
                $swoole->server['request_method'] = $method;
                $swoole->server['request_uri'] = "/v1/databases/$databaseId/collections/$collectionId/documents";
                $swoole->server['path_info'] = "/v1/databases/$databaseId/collections/$collectionId/documents";

                self::resolve($utopia, $request, $response, $resolve, $reject);
            }
        );
    }

    /**
     * Create a resolver for deleting a document in a specified database and collection.
     *
     * @param App $utopia
     * @param Database $dbForProject
     * @param string $databaseId
     * @param string $collectionId
     * @return callable
     */
    public static function resolveDocumentDelete(
        App $utopia,
        Database $dbForProject,
        string $databaseId,
        string $collectionId
    ): callable {
        return fn($type, $args, $context, $info) => new CoroutinePromise(
            function (callable $resolve, callable $reject) use ($utopia, $dbForProject, $databaseId, $collectionId, $type, $args) {
                $utopia = $utopia->getResource('current', true);
                $request = $utopia->getResource('request', true);
                $response = $utopia->getResource('response', true);
                $swoole = $request->getSwoole();
                $swoole->post = [
                    'databaseId' => $databaseId,
                    'collectionId' => $collectionId,
                    'documentId' => $args['id'],
                ];
                $swoole->server['request_method'] = 'DELETE';
                $swoole->server['request_uri'] = "/v1/databases/$databaseId/collections/$collectionId/documents/{$args['id']}";
                $swoole->server['path_info'] = "/v1/databases/$databaseId/collections/$collectionId/documents/{$args['id']}";

                self::resolve($utopia, $request, $response, $resolve, $reject);
            }
        );
    }

    /**
     * @param App $utopia
     * @param Request $request
     * @param Response $response
     * @param callable $resolve
     * @param callable $reject
     * @return void
     * @throws Exception
     */
    public static function resolve(
        App $utopia,
        Request $request,
        Response $response,
        callable $resolve,
        callable $reject,
    ): void {
        // Drop json content type so post args are used directly
        if ($request->getHeader('content-type') === 'application/json') {
            unset($request->getSwoole()->header['content-type']);
        }

        $request = new Request($request->getSwoole());
        $utopia->setResource('request', fn() => $request);
        $response->setContentType(Response::CONTENT_TYPE_NULL);

        try {
            $route = $utopia->match($request, fresh: true);

            $utopia->execute($route, $request);
        } catch (\Throwable $e) {
            $reject($e);
            return;
        }

        $payload = $response->getPayload();

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 400) {
            $reject(new GQLException($payload['message'], $response->getStatusCode()));
            return;
        }

        if (\array_key_exists('$id', $payload)) {
            $payload['_id'] = $payload['$id'];
        }

        $resolve($payload);
    }
}