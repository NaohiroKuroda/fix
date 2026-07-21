import AuthController from './AuthController'
import Quotation from './Quotation'
import OrderDelivery from './OrderDelivery'

const Controllers = {
    AuthController: Object.assign(AuthController, AuthController),
    Quotation: Object.assign(Quotation, Quotation),
    OrderDelivery: Object.assign(OrderDelivery, OrderDelivery),
}

export default Controllers