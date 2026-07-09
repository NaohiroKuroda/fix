import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Quotation\CancelApprovalController::confirm
* @see app/Http/Controllers/Quotation/CancelApprovalController.php:41
* @route '/quotation-management/cancel-approval'
*/
export const confirm = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

confirm.definition = {
    methods: ["post"],
    url: '/quotation-management/cancel-approval',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Quotation\CancelApprovalController::confirm
* @see app/Http/Controllers/Quotation/CancelApprovalController.php:41
* @route '/quotation-management/cancel-approval'
*/
confirm.url = (options?: RouteQueryOptions) => {
    return confirm.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\CancelApprovalController::confirm
* @see app/Http/Controllers/Quotation/CancelApprovalController.php:41
* @route '/quotation-management/cancel-approval'
*/
confirm.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

const cancelApproval = {
    confirm: Object.assign(confirm, confirm),
}

export default cancelApproval