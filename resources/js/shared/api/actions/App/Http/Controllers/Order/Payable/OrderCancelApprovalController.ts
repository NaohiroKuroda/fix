import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../../../../wayfinder'
/**
* @see \App\Http\Controllers\Order\Payable\OrderCancelApprovalController::index
* @see app/Http/Controllers/Order/Payable/OrderCancelApprovalController.php:16
* @route '/order-delivery/order-cancel-approval'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/order-delivery/order-cancel-approval',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\Order\Payable\OrderCancelApprovalController::index
* @see app/Http/Controllers/Order/Payable/OrderCancelApprovalController.php:16
* @route '/order-delivery/order-cancel-approval'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Order\Payable\OrderCancelApprovalController::index
* @see app/Http/Controllers/Order/Payable/OrderCancelApprovalController.php:16
* @route '/order-delivery/order-cancel-approval'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Order\Payable\OrderCancelApprovalController::index
* @see app/Http/Controllers/Order/Payable/OrderCancelApprovalController.php:16
* @route '/order-delivery/order-cancel-approval'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\Order\Payable\OrderCancelApprovalController::index
* @see app/Http/Controllers/Order/Payable/OrderCancelApprovalController.php:16
* @route '/order-delivery/order-cancel-approval'
*/
const indexForm = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Order\Payable\OrderCancelApprovalController::index
* @see app/Http/Controllers/Order/Payable/OrderCancelApprovalController.php:16
* @route '/order-delivery/order-cancel-approval'
*/
indexForm.get = (options?: RouteQueryOptions): RouteFormDefinition<'get'> => ({
    action: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\Order\Payable\OrderCancelApprovalController::index
* @see app/Http/Controllers/Order/Payable/OrderCancelApprovalController.php:16
* @route '/order-delivery/order-cancel-approval'
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
* @see \App\Http\Controllers\Order\Payable\OrderCancelApprovalController::confirm
* @see app/Http/Controllers/Order/Payable/OrderCancelApprovalController.php:26
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
* @see \App\Http\Controllers\Order\Payable\OrderCancelApprovalController::confirm
* @see app/Http/Controllers/Order/Payable/OrderCancelApprovalController.php:26
* @route '/order-delivery/order-cancel-approval'
*/
confirm.url = (options?: RouteQueryOptions) => {
    return confirm.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Order\Payable\OrderCancelApprovalController::confirm
* @see app/Http/Controllers/Order/Payable/OrderCancelApprovalController.php:26
* @route '/order-delivery/order-cancel-approval'
*/
confirm.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Order\Payable\OrderCancelApprovalController::confirm
* @see app/Http/Controllers/Order/Payable/OrderCancelApprovalController.php:26
* @route '/order-delivery/order-cancel-approval'
*/
const confirmForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Order\Payable\OrderCancelApprovalController::confirm
* @see app/Http/Controllers/Order/Payable/OrderCancelApprovalController.php:26
* @route '/order-delivery/order-cancel-approval'
*/
confirmForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

confirm.form = confirmForm

const OrderCancelApprovalController = { index, confirm }

export default OrderCancelApprovalController