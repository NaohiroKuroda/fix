import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Order\Payable\OrderApprovalController::approve
* @see app/Http/Controllers/Order/Payable/OrderApprovalController.php:26
* @route '/order-delivery/order-approval'
*/
export const approve = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(options),
    method: 'post',
})

approve.definition = {
    methods: ["post"],
    url: '/order-delivery/order-approval',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Order\Payable\OrderApprovalController::approve
* @see app/Http/Controllers/Order/Payable/OrderApprovalController.php:26
* @route '/order-delivery/order-approval'
*/
approve.url = (options?: RouteQueryOptions) => {
    return approve.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Order\Payable\OrderApprovalController::approve
* @see app/Http/Controllers/Order/Payable/OrderApprovalController.php:26
* @route '/order-delivery/order-approval'
*/
approve.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: approve.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Order\Payable\OrderApprovalController::approve
* @see app/Http/Controllers/Order/Payable/OrderApprovalController.php:26
* @route '/order-delivery/order-approval'
*/
const approveForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Order\Payable\OrderApprovalController::approve
* @see app/Http/Controllers/Order/Payable/OrderApprovalController.php:26
* @route '/order-delivery/order-approval'
*/
approveForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: approve.url(options),
    method: 'post',
})

approve.form = approveForm

/**
* @see \App\Http\Controllers\Order\Payable\OrderApprovalController::reject
* @see app/Http/Controllers/Order/Payable/OrderApprovalController.php:35
* @route '/order-delivery/order-approval/reject'
*/
export const reject = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(options),
    method: 'post',
})

reject.definition = {
    methods: ["post"],
    url: '/order-delivery/order-approval/reject',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Order\Payable\OrderApprovalController::reject
* @see app/Http/Controllers/Order/Payable/OrderApprovalController.php:35
* @route '/order-delivery/order-approval/reject'
*/
reject.url = (options?: RouteQueryOptions) => {
    return reject.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Order\Payable\OrderApprovalController::reject
* @see app/Http/Controllers/Order/Payable/OrderApprovalController.php:35
* @route '/order-delivery/order-approval/reject'
*/
reject.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Order\Payable\OrderApprovalController::reject
* @see app/Http/Controllers/Order/Payable/OrderApprovalController.php:35
* @route '/order-delivery/order-approval/reject'
*/
const rejectForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Order\Payable\OrderApprovalController::reject
* @see app/Http/Controllers/Order/Payable/OrderApprovalController.php:35
* @route '/order-delivery/order-approval/reject'
*/
rejectForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(options),
    method: 'post',
})

reject.form = rejectForm

const orderApproval = {
    approve: Object.assign(approve, approve),
    reject: Object.assign(reject, reject),
}

export default orderApproval