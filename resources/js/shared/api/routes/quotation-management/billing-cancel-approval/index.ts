import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Billing\BillingCancelApprovalController::confirm
* @see app/Http/Controllers/Billing/BillingCancelApprovalController.php:22
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
* @see \App\Http\Controllers\Billing\BillingCancelApprovalController::confirm
* @see app/Http/Controllers/Billing/BillingCancelApprovalController.php:22
* @route '/quotation-management/billing-cancel-approval'
*/
confirm.url = (options?: RouteQueryOptions) => {
    return confirm.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Billing\BillingCancelApprovalController::confirm
* @see app/Http/Controllers/Billing/BillingCancelApprovalController.php:22
* @route '/quotation-management/billing-cancel-approval'
*/
confirm.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Billing\BillingCancelApprovalController::confirm
* @see app/Http/Controllers/Billing/BillingCancelApprovalController.php:22
* @route '/quotation-management/billing-cancel-approval'
*/
const confirmForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Billing\BillingCancelApprovalController::confirm
* @see app/Http/Controllers/Billing/BillingCancelApprovalController.php:22
* @route '/quotation-management/billing-cancel-approval'
*/
confirmForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

confirm.form = confirmForm

const billingCancelApproval = {
    confirm: Object.assign(confirm, confirm),
}

export default billingCancelApproval