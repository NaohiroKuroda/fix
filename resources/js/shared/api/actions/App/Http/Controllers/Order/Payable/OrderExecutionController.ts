import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Order\Payable\OrderExecutionController::index
* @see app/Http/Controllers/Order/Payable/OrderExecutionController.php:16
* @route '/order-delivery/order-execution'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/order-delivery/order-execution',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Order\Payable\OrderExecutionController::index
* @see app/Http/Controllers/Order/Payable/OrderExecutionController.php:16
* @route '/order-delivery/order-execution'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Order\Payable\OrderExecutionController::index
* @see app/Http/Controllers/Order/Payable/OrderExecutionController.php:16
* @route '/order-delivery/order-execution'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Order\Payable\OrderExecutionController::index
* @see app/Http/Controllers/Order/Payable/OrderExecutionController.php:16
* @route '/order-delivery/order-execution'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Order\Payable\OrderExecutionController::index
* @see app/Http/Controllers/Order/Payable/OrderExecutionController.php:16
* @route '/order-delivery/order-execution'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Order\Payable\OrderExecutionController::index
* @see app/Http/Controllers/Order/Payable/OrderExecutionController.php:16
* @route '/order-delivery/order-execution'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Order\Payable\OrderExecutionController::index
* @see app/Http/Controllers/Order/Payable/OrderExecutionController.php:16
* @route '/order-delivery/order-execution'
*/
indexForm.head = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url({
        [options?.mergeQuery ? 'mergeQuery' : 'query']: {
            _method: 'HEAD',
            ...(options?.query ?? options?.mergeQuery ?? {}),
        }
    }),
    method: 'get',
})

index.form = indexForm

/**
* @see \App\Http\Controllers\Order\Payable\OrderExecutionController::execute
* @see app/Http/Controllers/Order/Payable/OrderExecutionController.php:25
* @route '/order-delivery/order-execution'
*/
export const execute = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: execute.url(options),
    method: 'post',
})

execute.definition = {
    methods: ["post"],
    url: '/order-delivery/order-execution',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Order\Payable\OrderExecutionController::execute
* @see app/Http/Controllers/Order/Payable/OrderExecutionController.php:25
* @route '/order-delivery/order-execution'
*/
execute.url = (options?: RouteQueryOptions) => {
    return execute.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Order\Payable\OrderExecutionController::execute
* @see app/Http/Controllers/Order/Payable/OrderExecutionController.php:25
* @route '/order-delivery/order-execution'
*/
execute.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: execute.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Order\Payable\OrderExecutionController::execute
* @see app/Http/Controllers/Order/Payable/OrderExecutionController.php:25
* @route '/order-delivery/order-execution'
*/
const executeForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: execute.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Order\Payable\OrderExecutionController::execute
* @see app/Http/Controllers/Order/Payable/OrderExecutionController.php:25
* @route '/order-delivery/order-execution'
*/
executeForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: execute.url(options),
    method: 'post',
})

execute.form = executeForm

const OrderExecutionController = { index, execute }

export default OrderExecutionController