import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\OrderDelivery\OrderCancelApprovalController::confirm
* @see app/Http/Controllers/OrderDelivery/OrderCancelApprovalController.php:26
* @route '/order-delivery/order-cancel-approval'
*/
export const confirm = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

confirm.definition = {
    methods: ["post"],
    url: '/order-delivery/order-cancel-approval',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\OrderDelivery\OrderCancelApprovalController::confirm
* @see app/Http/Controllers/OrderDelivery/OrderCancelApprovalController.php:26
* @route '/order-delivery/order-cancel-approval'
*/
confirm.url = (options?: RouteQueryOptions) => {
    return confirm.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OrderDelivery\OrderCancelApprovalController::confirm
* @see app/Http/Controllers/OrderDelivery/OrderCancelApprovalController.php:26
* @route '/order-delivery/order-cancel-approval'
*/
confirm.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderCancelApprovalController::confirm
* @see app/Http/Controllers/OrderDelivery/OrderCancelApprovalController.php:26
* @route '/order-delivery/order-cancel-approval'
*/
const confirmForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\OrderDelivery\OrderCancelApprovalController::confirm
* @see app/Http/Controllers/OrderDelivery/OrderCancelApprovalController.php:26
* @route '/order-delivery/order-cancel-approval'
*/
confirmForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

confirm.form = confirmForm

const orderCancelApproval = {
    confirm: Object.assign(confirm, confirm),
}

export default orderCancelApproval