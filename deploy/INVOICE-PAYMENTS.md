# Recording customer invoice payments

Open Admin → Invoices → an invoice. Issue a draft before recording money received.

In **Invoice payments → Record payment**:

1. Choose **Full payment** to settle the remaining balance, or **Partial payment** and enter the amount received in MYR.
2. Select Cash, Card, or FPX.
3. Enter the payment date and optional receipt / transaction reference and internal notes.
4. Select **Record payment**.

The invoice automatically becomes **Partially paid** or **Paid**. Payment history records the amount, method, date, reference, notes, recording staff member and timestamp. The printable invoice displays amount paid and balance due; internal payment notes and staff details are not printed.

This records payments received outside BRON. It does not process card or FPX payments, connect to a gateway, or verify bank settlement. Never enter card numbers or banking credentials.

Payment entries cannot currently be edited, deleted, refunded or reversed in the admin. Check details before recording. Invoices with recorded payments cannot be cancelled through the status dropdown. Existing invoices marked paid before this feature retain their status and display a notice that no payment details were recorded; no amounts or methods are invented for them. An admin may explicitly record their full historical payment details; partial entries are not accepted for these already settled invoices.

Deployment uses the normal GitHub Actions build and cPanel Pull / Deploy workflow. The additive migration creates invoice_payments; it does not change existing invoice totals or payment statuses. Take a database backup using cPanel before deployment.
