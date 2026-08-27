import BillingQuoteCreateController from './BillingQuoteCreateController'
import BillingQuoteApprovalController from './BillingQuoteApprovalController'
import BillingCancelRequestController from './BillingCancelRequestController'
import BillingCancelApprovalController from './BillingCancelApprovalController'
import BillingMessageController from './BillingMessageController'

const Billing = {
    BillingQuoteCreateController: Object.assign(BillingQuoteCreateController, BillingQuoteCreateController),
    BillingQuoteApprovalController: Object.assign(BillingQuoteApprovalController, BillingQuoteApprovalController),
    BillingCancelRequestController: Object.assign(BillingCancelRequestController, BillingCancelRequestController),
    BillingCancelApprovalController: Object.assign(BillingCancelApprovalController, BillingCancelApprovalController),
    BillingMessageController: Object.assign(BillingMessageController, BillingMessageController),
}

export default Billing