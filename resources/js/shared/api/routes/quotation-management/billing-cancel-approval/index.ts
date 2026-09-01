import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Quotation\Billing\BillingCancelApprovalController::confirm
* @see app/Http/Controllers/Quotation/Billing/BillingCancelApprovalController.php:26
* @route '/quotation-management/billing-cancel-approval'
*/
export const confirm = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

confirm.definition = {
    methods: ["post"],
    url: '/quotation-management/billing-cancel-approval',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingCancelApprovalController::confirm
* @see app/Http/Controllers/Quotation/Billing/BillingCancelApprovalController.php:26
* @route '/quotation-management/billing-cancel-approval'
*/
confirm.url = (options?: RouteQueryOptions) => {
    return confirm.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingCancelApprovalController::confirm
* @see app/Http/Controllers/Quotation/Billing/BillingCancelApprovalController.php:26
* @route '/quotation-management/billing-cancel-approval'
*/
confirm.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingCancelApprovalController::confirm
* @see app/Http/Controllers/Quotation/Billing/BillingCancelApprovalController.php:26
* @route '/quotation-management/billing-cancel-approval'
*/
const confirmForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingCancelApprovalController::confirm
* @see app/Http/Controllers/Quotation/Billing/BillingCancelApprovalController.php:26
* @route '/quotation-management/billing-cancel-approval'
*/
confirmForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

confirm.form = confirmForm

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingCancelApprovalController::reject
* @see app/Http/Controllers/Quotation/Billing/BillingCancelApprovalController.php:43
* @route '/quotation-management/billing-cancel-approval/reject'
*/
export const reject = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(options),
    method: 'post',
})

reject.definition = {
    methods: ["post"],
    url: '/quotation-management/billing-cancel-approval/reject',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingCancelApprovalController::reject
* @see app/Http/Controllers/Quotation/Billing/BillingCancelApprovalController.php:43
* @route '/quotation-management/billing-cancel-approval/reject'
*/
reject.url = (options?: RouteQueryOptions) => {
    return reject.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingCancelApprovalController::reject
* @see app/Http/Controllers/Quotation/Billing/BillingCancelApprovalController.php:43
* @route '/quotation-management/billing-cancel-approval/reject'
*/
reject.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingCancelApprovalController::reject
* @see app/Http/Controllers/Quotation/Billing/BillingCancelApprovalController.php:43
* @route '/quotation-management/billing-cancel-approval/reject'
*/
const rejectForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Quotation\Billing\BillingCancelApprovalController::reject
* @see app/Http/Controllers/Quotation/Billing/BillingCancelApprovalController.php:43
* @route '/quotation-management/billing-cancel-approval/reject'
*/
rejectForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(options),
    method: 'post',
})

reject.form = rejectForm

const billingCancelApproval = {
    confirm: Object.assign(confirm, confirm),
    reject: Object.assign(reject, reject),
}

export default billingCancelApproval