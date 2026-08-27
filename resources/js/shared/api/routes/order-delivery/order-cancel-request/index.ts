import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Order\Payable\OrderCancelRequestController::confirm
* @see app/Http/Controllers/Order/Payable/OrderCancelRequestController.php:25
* @route '/order-delivery/order-cancel-request'
*/
export const confirm = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

confirm.definition = {
    methods: ["post"],
    url: '/order-delivery/order-cancel-request',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Order\Payable\OrderCancelRequestController::confirm
* @see app/Http/Controllers/Order/Payable/OrderCancelRequestController.php:25
* @route '/order-delivery/order-cancel-request'
*/
confirm.url = (options?: RouteQueryOptions) => {
    return confirm.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Order\Payable\OrderCancelRequestController::confirm
* @see app/Http/Controllers/Order/Payable/OrderCancelRequestController.php:25
* @route '/order-delivery/order-cancel-request'
*/
confirm.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Order\Payable\OrderCancelRequestController::confirm
* @see app/Http/Controllers/Order/Payable/OrderCancelRequestController.php:25
* @route '/order-delivery/order-cancel-request'
*/
const confirmForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Order\Payable\OrderCancelRequestController::confirm
* @see app/Http/Controllers/Order/Payable/OrderCancelRequestController.php:25
* @route '/order-delivery/order-cancel-request'
*/
confirmForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

confirm.form = confirmForm

const orderCancelRequest = {
    confirm: Object.assign(confirm, confirm),
}

export default orderCancelRequest