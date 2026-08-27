import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Order\Payable\DeliveryReportController::index
* @see app/Http/Controllers/Order/Payable/DeliveryReportController.php:16
* @route '/order-delivery/delivery-report'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/order-delivery/delivery-report',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Order\Payable\DeliveryReportController::index
* @see app/Http/Controllers/Order/Payable/DeliveryReportController.php:16
* @route '/order-delivery/delivery-report'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Order\Payable\DeliveryReportController::index
* @see app/Http/Controllers/Order/Payable/DeliveryReportController.php:16
* @route '/order-delivery/delivery-report'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Order\Payable\DeliveryReportController::index
* @see app/Http/Controllers/Order/Payable/DeliveryReportController.php:16
* @route '/order-delivery/delivery-report'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Order\Payable\DeliveryReportController::index
* @see app/Http/Controllers/Order/Payable/DeliveryReportController.php:16
* @route '/order-delivery/delivery-report'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Order\Payable\DeliveryReportController::index
* @see app/Http/Controllers/Order/Payable/DeliveryReportController.php:16
* @route '/order-delivery/delivery-report'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Order\Payable\DeliveryReportController::index
* @see app/Http/Controllers/Order/Payable/DeliveryReportController.php:16
* @route '/order-delivery/delivery-report'
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
* @see \App\Http\Controllers\Order\Payable\DeliveryReportController::confirm
* @see app/Http/Controllers/Order/Payable/DeliveryReportController.php:25
* @route '/order-delivery/delivery-report'
*/
export const confirm = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

confirm.definition = {
    methods: ["post"],
    url: '/order-delivery/delivery-report',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Order\Payable\DeliveryReportController::confirm
* @see app/Http/Controllers/Order/Payable/DeliveryReportController.php:25
* @route '/order-delivery/delivery-report'
*/
confirm.url = (options?: RouteQueryOptions) => {
    return confirm.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Order\Payable\DeliveryReportController::confirm
* @see app/Http/Controllers/Order/Payable/DeliveryReportController.php:25
* @route '/order-delivery/delivery-report'
*/
confirm.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Order\Payable\DeliveryReportController::confirm
* @see app/Http/Controllers/Order/Payable/DeliveryReportController.php:25
* @route '/order-delivery/delivery-report'
*/
const confirmForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Order\Payable\DeliveryReportController::confirm
* @see app/Http/Controllers/Order/Payable/DeliveryReportController.php:25
* @route '/order-delivery/delivery-report'
*/
confirmForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

confirm.form = confirmForm

const DeliveryReportController = { index, confirm }

export default DeliveryReportController