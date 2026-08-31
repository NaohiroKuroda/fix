import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Order\Payable\OrderAcceptanceController::index
* @see app/Http/Controllers/Order/Payable/OrderAcceptanceController.php:21
* @route '/order-delivery/order-acceptance'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/order-delivery/order-acceptance',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Order\Payable\OrderAcceptanceController::index
* @see app/Http/Controllers/Order/Payable/OrderAcceptanceController.php:21
* @route '/order-delivery/order-acceptance'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Order\Payable\OrderAcceptanceController::index
* @see app/Http/Controllers/Order/Payable/OrderAcceptanceController.php:21
* @route '/order-delivery/order-acceptance'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Order\Payable\OrderAcceptanceController::index
* @see app/Http/Controllers/Order/Payable/OrderAcceptanceController.php:21
* @route '/order-delivery/order-acceptance'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Order\Payable\OrderAcceptanceController::index
* @see app/Http/Controllers/Order/Payable/OrderAcceptanceController.php:21
* @route '/order-delivery/order-acceptance'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Order\Payable\OrderAcceptanceController::index
* @see app/Http/Controllers/Order/Payable/OrderAcceptanceController.php:21
* @route '/order-delivery/order-acceptance'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Order\Payable\OrderAcceptanceController::index
* @see app/Http/Controllers/Order/Payable/OrderAcceptanceController.php:21
* @route '/order-delivery/order-acceptance'
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

const OrderAcceptanceController = { index }

export default OrderAcceptanceController