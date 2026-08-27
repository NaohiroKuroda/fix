import QuoteRequestController from './QuoteRequestController'
import VendorSelectionController from './VendorSelectionController'
import ManagerApprovalController from './ManagerApprovalController'
import CancelRequestController from './CancelRequestController'
import CancelApprovalController from './CancelApprovalController'
import PayableMessageController from './PayableMessageController'

const Payable = {
    QuoteRequestController: Object.assign(QuoteRequestController, QuoteRequestController),
    VendorSelectionController: Object.assign(VendorSelectionController, VendorSelectionController),
    ManagerApprovalController: Object.assign(ManagerApprovalController, ManagerApprovalController),
    CancelRequestController: Object.assign(CancelRequestController, CancelRequestController),
    CancelApprovalController: Object.assign(CancelApprovalController, CancelApprovalController),
    PayableMessageController: Object.assign(PayableMessageController, PayableMessageController),
}

export default Payable