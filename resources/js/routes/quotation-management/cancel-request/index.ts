import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Quotation\CancelRequestController::confirm
* @see app/Http/Controllers/Quotation/CancelRequestController.php:41
* @route '/quotation-management/cancel-request'
*/
export const confirm = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

confirm.definition = {
    methods: ["post"],
    url: '/quotation-management/cancel-request',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Quotation\CancelRequestController::confirm
* @see app/Http/Controllers/Quotation/CancelRequestController.php:41
* @route '/quotation-management/cancel-request'
*/
confirm.url = (options?: RouteQueryOptions) => {
    return confirm.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Quotation\CancelRequestController::confirm
* @see app/Http/Controllers/Quotation/CancelRequestController.php:41
* @route '/quotation-management/cancel-request'
*/
confirm.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Quotation\CancelRequestController::confirm
* @see app/Http/Controllers/Quotation/CancelRequestController.php:41
* @route '/quotation-management/cancel-request'
*/
const confirmForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Quotation\CancelRequestController::confirm
* @see app/Http/Controllers/Quotation/CancelRequestController.php:41
* @route '/quotation-management/cancel-request'
*/
confirmForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

confirm.form = confirmForm

const cancelRequest = {
    confirm: Object.assign(confirm, confirm),
}

export default cancelRequest