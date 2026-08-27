import OrderExecutionController from './OrderExecutionController'
import OrderApprovalController from './OrderApprovalController'
import OrderCancelRequestController from './OrderCancelRequestController'
import OrderCancelApprovalController from './OrderCancelApprovalController'
import OrderAcceptanceController from './OrderAcceptanceController'
import DeliveryReportController from './DeliveryReportController'
import DeliveryApprovalController from './DeliveryApprovalController'
import InvoiceApprovalController from './InvoiceApprovalController'

const Payable = {
    OrderExecutionController: Object.assign(OrderExecutionController, OrderExecutionController),
    OrderApprovalController: Object.assign(OrderApprovalController, OrderApprovalController),
    OrderCancelRequestController: Object.assign(OrderCancelRequestController, OrderCancelRequestController),
    OrderCancelApprovalController: Object.assign(OrderCancelApprovalController, OrderCancelApprovalController),
    OrderAcceptanceController: Object.assign(OrderAcceptanceController, OrderAcceptanceController),
    DeliveryReportController: Object.assign(DeliveryReportController, DeliveryReportController),
    DeliveryApprovalController: Object.assign(DeliveryApprovalController, DeliveryApprovalController),
    InvoiceApprovalController: Object.assign(InvoiceApprovalController, InvoiceApprovalController),
}

export default Payable