import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\OrderDelivery\OrderAcceptanceController::record
* @see app/Http/Controllers/OrderDelivery/OrderAcceptanceController.php:26
* @route '/order-delivery/order-acceptance'
*/
export const record = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: record.url(options),
    method: 'post',
})

record.definition = {
    methods: ["post"],
    url: '/order-delivery/order-acceptance',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\OrderDelivery\OrderAcceptanceController::record
* @see app/Http/Controllers/OrderDelivery/OrderAcceptanceController.php:26
* @route '/order-delivery/order-acceptance'
*/
record.url = (options?: RouteQueryOptions) => {
    return record.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrderDelivery\OrderAcceptanceController::record
* @see app/Http/Controllers/OrderDelivery/OrderAcceptanceController.php:26
* @route '/order-delivery/order-acceptance'
*/
record.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: record.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderAcceptanceController::record
* @see app/Http/Controllers/OrderDelivery/OrderAcceptanceController.php:26
* @route '/order-delivery/order-acceptance'
*/
const recordForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: record.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderAcceptanceController::record
* @see app/Http/Controllers/OrderDelivery/OrderAcceptanceController.php:26
* @route '/order-delivery/order-acceptance'
*/
recordForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: record.url(options),
    method: 'post',
})

record.form = recordForm

/**
* @see \App\Http\Controllers\OrderDelivery\OrderAcceptanceController::renotify
* @see app/Http/Controllers/OrderDelivery/OrderAcceptanceController.php:0
* @route '/order-delivery/order-acceptance/renotify'
*/
export const renotify = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: renotify.url(options),
    method: 'post',
})

renotify.definition = {
    methods: ["post"],
    url: '/order-delivery/order-acceptance/renotify',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\OrderDelivery\OrderAcceptanceController::renotify
* @see app/Http/Controllers/OrderDelivery/OrderAcceptanceController.php:0
* @route '/order-delivery/order-acceptance/renotify'
*/
renotify.url = (options?: RouteQueryOptions) => {
    return renotify.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrderDelivery\OrderAcceptanceController::renotify
* @see app/Http/Controllers/OrderDelivery/OrderAcceptanceController.php:0
* @route '/order-delivery/order-acceptance/renotify'
*/
renotify.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: renotify.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderAcceptanceController::renotify
* @see app/Http/Controllers/OrderDelivery/OrderAcceptanceController.php:0
* @route '/order-delivery/order-acceptance/renotify'
*/
const renotifyForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: renotify.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderAcceptanceController::renotify
* @see app/Http/Controllers/OrderDelivery/OrderAcceptanceController.php:0
* @route '/order-delivery/order-acceptance/renotify'
*/
renotifyForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: renotify.url(options),
    method: 'post',
})

renotify.form = renotifyForm

/**
* @see \App\Http\Controllers\OrderDelivery\OrderAcceptanceController::cancelRequest
* @see app/Http/Controllers/OrderDelivery/OrderAcceptanceController.php:36
* @route '/order-delivery/order-acceptance/cancel-request'
*/
export const cancelRequest = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancelRequest.url(options),
    method: 'post',
})

cancelRequest.definition = {
    methods: ["post"],
    url: '/order-delivery/order-acceptance/cancel-request',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\OrderDelivery\OrderAcceptanceController::cancelRequest
* @see app/Http/Controllers/OrderDelivery/OrderAcceptanceController.php:36
* @route '/order-delivery/order-acceptance/cancel-request'
*/
cancelRequest.url = (options?: RouteQueryOptions) => {
    return cancelRequest.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrderDelivery\OrderAcceptanceController::cancelRequest
* @see app/Http/Controllers/OrderDelivery/OrderAcceptanceController.php:36
* @route '/order-delivery/order-acceptance/cancel-request'
*/
cancelRequest.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: cancelRequest.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderAcceptanceController::cancelRequest
* @see app/Http/Controllers/OrderDelivery/OrderAcceptanceController.php:36
* @route '/order-delivery/order-acceptance/cancel-request'
*/
const cancelRequestForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancelRequest.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderAcceptanceController::cancelRequest
* @see app/Http/Controllers/OrderDelivery/OrderAcceptanceController.php:36
* @route '/order-delivery/order-acceptance/cancel-request'
*/
cancelRequestForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: cancelRequest.url(options),
    method: 'post',
})

cancelRequest.form = cancelRequestForm

const orderAcceptance = {
    record: Object.assign(record, record),
    renotify: Object.assign(renotify, renotify),
    cancelRequest: Object.assign(cancelRequest, cancelRequest),
}

export default orderAcceptance