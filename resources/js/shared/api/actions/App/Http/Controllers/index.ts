import AuthController from './AuthController'
import Quotation from './Quotation'
import Comment from './Comment'
import Order from './Order'

const Controllers = {
    AuthController: Object.assign(AuthController, AuthController),
    Quotation: Object.assign(Quotation, Quotation),
    Comment: Object.assign(Comment, Comment),
    Order: Object.assign(Order, Order),
}

export default Controllers