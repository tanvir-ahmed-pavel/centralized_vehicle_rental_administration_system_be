<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function getInvoiceStatistics(Request $request)
    {
        // Check if the clientInvoice belongs to the logged-in user's company
        $user = Auth::user();
        $company = $user->company;
        if (!$company) {
            return response()->json(['error' => 'Client not found or unauthorized'], 404);
        }

        $clientInvoices = $company->clientInvoices()->get();
        $vendorInvoices = $company->vendorInvoices()->get();
        $driverInvoices = $company->driverInvoices()->get();

        $lifetime_received_from_client = $clientInvoices->sum("total_paid");
        $current_receivable_from_client = $clientInvoices->sum("grand_total") - $lifetime_received_from_client;

        $lifetime_paid_to_driver = $driverInvoices->sum("total_paid");
        $lifetime_paid_to_vendor = $vendorInvoices->sum("total_paid");

        $current_payable_to_vendor = ($vendorInvoices->sum("grand_total")) - $lifetime_paid_to_vendor;
        $current_payable_to_driver = ($driverInvoices->sum("grand_total")) - $lifetime_paid_to_driver;

        $clientInvoiceCount = $clientInvoices->count();
        $vendorInvoiceCount = $vendorInvoices->count();
        $driverInvoiceCount = $driverInvoices->count();
        $totalInvoiceCount = $clientInvoiceCount + $vendorInvoiceCount + $driverInvoiceCount;

        return response()->json([
            'message' => 'Invoice statistics retrieved successfully',
            'data' => [
                'current_receivable_from_client' => $current_receivable_from_client,
                'current_payable_to_driver' => $current_payable_to_driver,
                'current_payable_to_vendor' => $current_payable_to_vendor,
                'lifetime_received_from_client' => $lifetime_received_from_client,
                'lifetime_paid_to_driver' => $lifetime_paid_to_driver,
                'lifetime_paid_to_vendor' => $lifetime_paid_to_vendor,
                'client_invoice_count' => $clientInvoiceCount,
                'vendor_invoice_count' => $vendorInvoiceCount,
                'driver_invoice_count' => $driverInvoiceCount,
                'total_invoice_count' => $totalInvoiceCount,
            ]

        ], 200);

    }

    public function getInvoice(Request $request){

        // Set default values for pagination if not provided in the request
        $page = $request->has('page') ? $request->page : 1;
        $perPage = $request->has('per_page') ? $request->per_page : 10;

        // Set default values for sorting if not provided in the request
        $sortBy = $request->has('sort_by') ? $request->sort_by : 'date';
        $sortOrder = $request->has('sort_order') ? $request->sort_order : 'desc';


        // Check if the clientInvoice belongs to the logged-in user's company
        $user = Auth::user();
        $company = $user->company;
        if (!$company) {
            return response()->json(['error' => 'Client not found or unauthorized'], 404);
        }

        $clientInvoices = $company->clientInvoices()->select(
            'id',
            'company_id',
            'daily_basis_id',
            'monthly_contract_id',
            'vehicle_id',
            DB::raw('null as vendor_id'),
            'client_id',
            'driver_id',
            'status',
            'invoice_number',
            'invoice_date',
            'due_date',
            'for_the_month_of',
            'sub_total',
            'advance_amount',
            'discount_amount',
            'tax_percent',
            'vat_percent',
            'tax_amount',
            'vat_amount',
            'grand_total',
            'total_paid',
            'round_adjustment',
            'round_total',
            'remarks',
            'created_at',
        )->orderBy($sortBy, $sortOrder);
        $vendorInvoices = $company->vendorInvoices()->select(
            'id',
            'company_id',
            'daily_basis_id',
            'monthly_contract_id',
            'vehicle_id',
            'vendor_id',
            'client_id',
            'driver_id',
            'status',
            'invoice_number',
            'for_the_month_of',
            'invoice_date',
            'due_date',
            'sub_total',
            'advance_amount',
            'discount_amount',
            'tax_percent',
            'vat_percent',
            'tax_amount',
            'vat_amount',
            'grand_total',
            'total_paid',
            'round_adjustment',
            'round_total',
            'remarks',
            'created_at',
        )->orderBy($sortBy, $sortOrder);
        $driverInvoices = $company->driverInvoices()->select(
            'id',
            'company_id',
            'daily_basis_id',
            'monthly_contract_id',
            'vehicle_id',
            DB::raw('null as vendor_id'),
            'client_id',
            'driver_id',
            'status',
            'invoice_number',
            'for_the_month_of',
            'invoice_date',
            'due_date',
            'sub_total',
            'advance_amount',
            'discount_amount',
            'tax_percent',
            'vat_percent',
            'tax_amount',
            'vat_amount',
            'grand_total',
            'total_paid',
            'round_adjustment',
            'round_total',
            'remarks',
            'created_at',
        )->orderBy($sortBy, $sortOrder);

        $lifetime_received_from_client = $clientInvoices->sum("total_paid");
        $current_receivable_from_client = $clientInvoices->sum("grand_total") - $lifetime_received;

        $lifetime_paid_to_driver = $driverInvoices->sum("total_paid");
        $lifetime_paid_to_vendor = $vendorInvoices->sum("total_paid");

        $current_payable_to_vendor = ($vendorInvoices->sum("grand_total")) - $lifetime_paid_to_vendor;
        $current_payable_to_driver = ($driverInvoices->sum("grand_total")) - $lifetime_paid_to_driver;

        $clientInvoiceCount = $clientInvoices->count();
        $vendorInvoiceCount = $vendorInvoices->count();
        $driverInvoiceCount = $driverInvoices->count();
        $totalInvoiceCount = $clientInvoiceCount + $vendorInvoiceCount + $driverInvoiceCount;

        $invoices = $clientInvoices
            ->unionAll($driverInvoices)
            ->unionAll($vendorInvoices)
            ->with(['vehicle:id,name,model_year,reg_no', 'client:id,name,address,mobile_no', 'vendor:id,name,address,mobile_no', 'driver:id,name,mobile_no', 'invoiceItems'])
            ->orderBy($sortBy, $sortOrder)
            ->when($request->has('status'), function ($query) use ($request) {
                $statuses = explode(',', $request->status);
                return $query->whereIn('status', $statuses);
            })
            ->when($request->has('start_date') && $request->has('end_date'), function ($query) use ($request) {
                return $query->whereBetween('invoice_date', [$request->start_date, $request->end_date]);
            })
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'message' => 'All invoices retrieved successfully',
            'data' => $invoices,
            'current_receivable_from_client' => $current_receivable_from_client,
            'current_payable_to_driver' => $current_payable_to_driver,
            'current_payable_to_vendor' => $current_payable_to_vendor,
            'lifetime_received_from_client' => $lifetime_received_from_client,
            'lifetime_paid_to_driver' => $lifetime_paid_to_driver,
            'lifetime_paid_to_vendor' => $lifetime_paid_to_vendor,
            'client_invoice_count' => $clientInvoiceCount,
            'vendor_invoice_count' => $vendorInvoiceCount,
            'driver_invoice_count' => $driverInvoiceCount,
            'total_invoice_count' => $totalInvoiceCount,
        ], 200);

    }
}
