import BillingQuoteCreateController from './BillingQuoteCreateController'
import BillingQuoteApprovalController from './BillingQuoteApprovalController'
import BillingCancelRequestController from './BillingCancelRequestController'
import BillingCancelApprovalController from './BillingCancelApprovalController'
import BillingOrderConfirmationController from './BillingOrderConfirmationController'

const Billing = {
    BillingQuoteCreateController: Object.assign(BillingQuoteCreateController, BillingQuoteCreateController),
    BillingQuoteApprovalController: Object.assign(BillingQuoteApprovalController, BillingQuoteApprovalController),
    BillingCancelRequestController: Object.assign(BillingCancelRequestController, BillingCancelRequestController),
    BillingCancelApprovalController: Object.assign(BillingCancelApprovalController, BillingCancelApprovalController),
    BillingOrderConfirmationController: Object.assign(BillingOrderConfirmationController, BillingOrderConfirmationController),
}

export default Billing