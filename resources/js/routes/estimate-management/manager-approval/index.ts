import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::confirm
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:41
* @route '/estimate-management/manager-approval'
*/
export const confirm = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

confirm.definition = {
    methods: ["post"],
    url: '/estimate-management/manager-approval',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::confirm
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:41
* @route '/estimate-management/manager-approval'
*/
confirm.url = (options?: RouteQueryOptions) => {
    return confirm.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::confirm
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:41
* @route '/estimate-management/manager-approval'
*/
confirm.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

const managerApproval = {
    confirm: Object.assign(confirm, confirm),
}

export default managerApproval