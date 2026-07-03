import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::confirm
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:42
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
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:42
* @route '/estimate-management/manager-approval'
*/
confirm.url = (options?: RouteQueryOptions) => {
    return confirm.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::confirm
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:42
* @route '/estimate-management/manager-approval'
*/
confirm.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::confirm
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:42
* @route '/estimate-management/manager-approval'
*/
const confirmForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::confirm
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:42
* @route '/estimate-management/manager-approval'
*/
confirmForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

confirm.form = confirmForm

/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::reject
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:59
* @route '/estimate-management/manager-approval/reject'
*/
export const reject = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(options),
    method: 'post',
})

reject.definition = {
    methods: ["post"],
    url: '/estimate-management/manager-approval/reject',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::reject
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:59
* @route '/estimate-management/manager-approval/reject'
*/
reject.url = (options?: RouteQueryOptions) => {
    return reject.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::reject
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:59
* @route '/estimate-management/manager-approval/reject'
*/
reject.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: reject.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::reject
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:59
* @route '/estimate-management/manager-approval/reject'
*/
const rejectForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Quotation\ManagerApprovalController::reject
* @see app/Http/Controllers/Quotation/ManagerApprovalController.php:59
* @route '/estimate-management/manager-approval/reject'
*/
rejectForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: reject.url(options),
    method: 'post',
})

reject.form = rejectForm

const managerApproval = {
    confirm: Object.assign(confirm, confirm),
    reject: Object.assign(reject, reject),
}

export default managerApproval