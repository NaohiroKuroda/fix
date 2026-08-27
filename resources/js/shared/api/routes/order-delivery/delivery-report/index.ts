import { queryParams, type RouteQueryOptions, type RouteDefinition, type RouteFormDefinition } from './../../../wayfinder'
/**
* @see \App\Http\Controllers\Order\Payable\DeliveryReportController::confirm
* @see app/Http/Controllers/Order/Payable/DeliveryReportController.php:25
* @route '/order-delivery/delivery-report'
*/
export const confirm = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

confirm.definition = {
    methods: ["post"],
    url: '/order-delivery/delivery-report',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\Order\Payable\DeliveryReportController::confirm
* @see app/Http/Controllers/Order/Payable/DeliveryReportController.php:25
* @route '/order-delivery/delivery-report'
*/
confirm.url = (options?: RouteQueryOptions) => {
    return confirm.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\Order\Payable\DeliveryReportController::confirm
* @see app/Http/Controllers/Order/Payable/DeliveryReportController.php:25
* @route '/order-delivery/delivery-report'
*/
confirm.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Order\Payable\DeliveryReportController::confirm
* @see app/Http/Controllers/Order/Payable/DeliveryReportController.php:25
* @route '/order-delivery/delivery-report'
*/
const confirmForm = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\Order\Payable\DeliveryReportController::confirm
* @see app/Http/Controllers/Order/Payable/DeliveryReportController.php:25
* @route '/order-delivery/delivery-report'
*/
confirmForm.post = (options?: RouteQueryOptions): RouteFormDefinition<'post'> => ({
    action: confirm.url(options),
    method: 'post',
})

confirm.form = confirmForm

const deliveryReport = {
    confirm: Object.assign(confirm, confirm),
}

export default deliveryReport