import AuthController from './AuthController'
import Quotation from './Quotation'
import Billing from './Billing'
import OrderDelivery from './OrderDelivery'

const Controllers = {
    AuthController: Object.assign(AuthController, AuthController),
    Quotation: Object.assign(Quotation, Quotation),
    Billing: Object.assign(Billing, Billing),
    OrderDelivery: Object.assign(OrderDelivery, OrderDelivery),
}

export default Controllers