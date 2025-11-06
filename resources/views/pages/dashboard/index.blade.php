@extends('layouts.app')

@section('styles')
    <style>
        .collapse-toggle i.fa-angle-down {
            transition: transform .2s;
        }

        .collapse-toggle.collapsed i.fa-angle-down {
            transform: rotate(-90deg);
        }

        .card-header::after {
            display: none !important;
        }


        #process-flow-container, #process-flow-row2 {
            margin-left: -8px;
            margin-right: -8px;
        }
        
        #process-flow-container > div, #process-flow-row2 > div {
            padding-left: 8px;
            padding-right: 8px;
        }
        
        .process-flow-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1), 0 4px 8px rgba(0, 0, 0, 0.06);
            height: 77px;
            display: flex;
            align-items: center;
            padding: 20px 16px;
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
            width: 100%;
            margin-bottom: 16px;
        }

        .process-flow-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12), 0 8px 16px rgba(0, 0, 0, 0.08);
        }

        .process-flow-icon {
            width: 46px;
            height: 46px;
            background-color: #F8F9FD;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 14px;
            flex-shrink: 0;
        }

        .process-flow-icon i {
            color: #115641;
            font-size: 16px;
        }

        .process-flow-content {
            flex: 1;
            min-width: 0;
        }

        .process-flow-title {
            font-size: 12px;
            font-weight: 500;
            color: #115641;
            margin: 0 0 3px 0;
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .process-flow-count {
            font-size: 20px;
            font-weight: bold;
            color: #115641;
            margin: 0 0 3px 0;
            line-height: 1;
        }

        .process-flow-atr {
            font-size: 10px;
            font-weight: 400;
            color: #6B7280;
            margin: 0;
            line-height: 1;
        }

        
        @media (max-width: 1199px) {
            .process-flow-card {
                height: 88px;
                padding: 18px 14px;
            }
            
            .process-flow-icon {
                width: 42px;
                height: 42px;
                margin-right: 12px;
            }
            
            .process-flow-icon i {
                font-size: 15px;
            }
            
            .process-flow-count {
                font-size: 18px;
            }
        }

        @media (max-width: 991px) {
            .process-flow-card {
                height: 85px;
                padding: 16px 12px;
            }
            
            .process-flow-icon {
                width: 40px;
                height: 40px;
                margin-right: 10px;
            }
            
            .process-flow-icon i {
                font-size: 14px;
            }
            
            .process-flow-count {
                font-size: 17px;
            }
            
            .process-flow-title {
                font-size: 11px;
            }
            
            .process-flow-atr {
                font-size: 9px;
            }
        }

        @media (max-width: 575px) {
            .process-flow-card {
                height: auto;
                min-height: 80px;
                padding: 16px;
            }
            
            .process-flow-icon {
                width: 44px;
                height: 44px;
                margin-right: 14px;
            }
            
            .process-flow-icon i {
                font-size: 16px;
            }
            
            .process-flow-count {
                font-size: 20px;
            }
            
            .process-flow-title {
                font-size: 13px;
            }
            
            .process-flow-atr {
                font-size: 11px;
            }
        }
        
        /* Custom 5-column grid for 20% width each */
        .col-xl-2-4 {
            flex: 0 0 20%;
            max-width: 20%;
        }
        
        @media (max-width: 1199px) {
            .col-xl-2-4 {
                flex: 0 0 25%;
                max-width: 25%;
            }
        }
        
        @media (max-width: 991px) {
            .col-xl-2-4 {
                flex: 0 0 50%;
                max-width: 50%;
            }
        }
        
        @media (max-width: 575px) {
            .col-xl-2-4 {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }

        /* Chart Cards Styling - Modern Design */
        .chart-card {
            border-radius: 16px;
            background: #fff;
            transition: all 0.3s ease;
        }
        
        .chart-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1) !important;
        }

        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: #115641;
            margin: 0;
            line-height: 1.3;
        }

        .chart-subtitle {
            font-size: 13px;
            font-weight: 400;
            color: #6B7280;
            margin: 0;
            line-height: 1.2;
        }

        .chart-controls {
            gap: 12px;
        }

        .control-item {
            flex: 0 0 auto;
        }

        .modern-select,
        .modern-input {
            border: 1.5px solid #E5E7EB;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 500;
            color: #374151;
            background: #fff;
            transition: all 0.2s ease;
            min-width: 120px;
            height: 40px; /* Fixed height for consistency */
            line-height: 1.5;
            display: inline-flex;
            align-items: center;
        }

        .modern-select:focus,
        .modern-input:focus {
            border-color: #115641;
            box-shadow: 0 0 0 3px rgba(17, 86, 65, 0.1);
            outline: none;
        }

        .modern-apply-btn {
            background: #115641;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 20px;
            font-size: 13px;
            height: 40px; /* Match input/select height */
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            transition: all 0.2s ease;
            min-width: 80px;
        }

        .modern-apply-btn:hover {
            background: #0F4A37;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(17, 86, 65, 0.3);
        }

        .chart-container {
            background: #FAFBFC;
            border-radius: 12px;
            padding: 16px;
            border: 1px solid #F1F3F4;
        }

        /* SOURCE CONVERSION LISTS Styling */
        .source-conversion-section {
            background: #f8f9fa !important;
            border-radius: 15px !important;
        }
        
        .source-conversion-section .form-label {
            height: auto;
            line-height: 1.4;
            margin-bottom: 8px !important;
            font-size: 13px;
            font-weight: 600;
            color: #495057;
            display: block;
        }

        .source-control-input {
            height: 42px;
            border: 1px solid #d1d3e2;
            border-radius: 8px;
            font-size: 14px;
            background: #fff;
            padding: 10px 12px;
            transition: all 0.3s ease;
            width: 100%;
        }

        .source-control-input:focus {
            border-color: #115641;
            box-shadow: 0 0 0 0.2rem rgba(17, 86, 65, 0.25);
        }

        /* Source dropdown specific styling */
        #source-filter {
            max-width: 160px;
        }

        #source-filter option {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 160px;
        }

        /* Source monitoring filter */
        #source-monitoring-source-filter {
            max-width: 140px;
        }

        #source-monitoring-source-filter option {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 140px;
        }

        .source-apply-button {
            height: 42px;
            background: #115641;
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .source-apply-button:hover {
            background: #0d4133;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(17, 86, 65, 0.3);
        }

        /* Responsive improvements for SOURCE CONVERSION LISTS filters */
        @media (max-width: 768px) {
            .source-conversion-section {
                padding: 20px !important;
            }
            
            .source-conversion-section .row {
                margin-bottom: 15px !important;
            }
            
            .source-conversion-section .row:last-child {
                margin-bottom: 0 !important;
            }
            
            .source-conversion-section .col-md-6 {
                margin-bottom: 15px;
            }
            
            .source-apply-button {
                margin-top: 10px;
            }
        }
        
        @media (max-width: 576px) {
            .source-conversion-section {
                padding: 15px !important;
            }
            
            .source-conversion-section .form-label {
                font-size: 12px;
                margin-bottom: 6px !important;
            }
            
            .source-control-input {
                height: 40px;
                font-size: 13px;
                padding: 8px 10px;
            }
            
            .source-apply-button {
                height: 40px;
                font-size: 13px;
            }
        }

        /* COMPREHENSIVE FIX: Remove border-radius from ALL table elements */
        .table, .table-responsive, .source-conversion-responsive, .table-with-sticky-footer,
        #source-conversion-table, #potential-branch-table, #potential-list-table,
        .table *, .table-responsive *, .source-conversion-responsive *, 
        .table-with-sticky-footer *, .potential-dealing-responsive * {
            border-radius: 0 !important;
            -webkit-border-radius: 0 !important;
            -moz-border-radius: 0 !important;
        }

        .table th, .table td, thead th, tbody td, th, td,
        .source-conversion-responsive th, .table-with-sticky-footer th,
        .table thead th:first-child, .table thead th:last-child,
        .table tbody td:first-child, .table tbody td:last-child {
            border-radius: 0 !important;
            -webkit-border-radius: 0 !important;
            -moz-border-radius: 0 !important;
        }

        /* Container styling without border-radius */
        .source-conversion-table-container, .table-with-sticky-footer, 
        .potential-dealing-responsive, .table-container,
        .card .table, .card .table-responsive {
            border-radius: 0 !important;
            -webkit-border-radius: 0 !important;
            -moz-border-radius: 0 !important;
        }

        /* Bootstrap table overrides */
        .table-bordered, .table-bordered th, .table-bordered td,
        .rounded, .rounded-top, .rounded-bottom, .rounded-left, .rounded-right {
            border-radius: 0 !important;
            -webkit-border-radius: 0 !important;
            -moz-border-radius: 0 !important;
        }

        #source-conversion-table {
            border-radius: 0 !important;
            overflow: hidden;
        }

        #source-conversion-table thead th {
            border: none;
            font-size: 14px;
            border-radius: 0 !important;
        }

        #source-conversion-table tbody td {
            border-top: 1px solid #f1f3f4;
            padding: 16px;
            font-size: 14px;
            vertical-align: middle;
        }

        #source-conversion-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Source Conversion Table Container with Max 5 Rows */
        .source-conversion-table-container {
            max-height: 350px; /* Height for exactly 5 rows + header */
            overflow-y: auto;
            overflow-x: auto;
            border-radius: 0 !important;
            border: 1px solid #e3e6f0;
            background-color: #fff;
        }

        /* Ensure table takes full width */
        .source-conversion-table-container .table {
            margin-bottom: 0;
        }

        /* Sticky header styling */
        .source-conversion-table-container thead th {
            background-color: #115641 !important;
            border: none;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        /* Table row height calculation for exactly 5 visible rows */
        #source-conversion-table tbody td {
            padding: 16px;
            border-top: 1px solid #f1f3f4;
            font-size: 14px;
            vertical-align: middle;
        }

        /* Custom scrollbar for better UX */
        .source-conversion-table-container::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .source-conversion-table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .source-conversion-table-container::-webkit-scrollbar-thumb {
            background: #115641;
            border-radius: 4px;
        }

        .source-conversion-table-container::-webkit-scrollbar-thumb:hover {
            background: #0d4133;
        }

        /* SOURCE CONVERSION LISTS specific styling for cross-browser compatibility */
        .source-conversion-responsive {
            position: relative;
            min-height: 200px; /* Minimum height for small data */
            max-height: 400px; /* Maximum height for large data */
            overflow: hidden;
            border-radius: 20px;
            border: 1px solid #e3e6f0;
            background-color: #fff;
            display: flex;
            flex-direction: column;
        }
        
        .source-conversion-scroll {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
            /* Firefox scrollbar styling */
            scrollbar-width: thin;
            scrollbar-color: #115641 #f1f1f1;
        }
        
        /* Chrome/Safari scrollbar styling */
        .source-conversion-scroll::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        .source-conversion-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .source-conversion-scroll::-webkit-scrollbar-thumb {
            background: #115641;
            border-radius: 4px;
        }
        
        .source-conversion-scroll::-webkit-scrollbar-thumb:hover {
            background: #0d4133;
        }
        
        /* Ensure table takes full width and proper display */
        .source-conversion-responsive table {
            width: 100%;
            margin-bottom: 0;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        /* Sticky header for all browsers */
        .source-conversion-responsive thead {
            flex-shrink: 0;
            position: relative;
            z-index: 20;
            background-color: #115641;
            display: block;
        }
        
        .source-conversion-responsive thead th {
            background-color: #115641 !important;
            border: none;
        }
        
        /* Scrollable body */
        .source-conversion-responsive tbody {
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            display: block;
        }
        
        /* Sticky footer for all browsers */
        .source-conversion-responsive tfoot {
            flex-shrink: 0;
            position: relative;
            z-index: 15;
            background-color: #115641;
            display: block;
        }
        
        .source-conversion-responsive tfoot td {
            background-color: #115641 !important;
            border: none;
        }
        
        /* Table row styling for flexbox layout */
        .source-conversion-responsive tr {
            display: table;
            width: 100%;
            table-layout: fixed;
        }
        
        /* Body content styling */
        .source-conversion-responsive tbody td {
            padding: 12px 16px;
            border-top: 1px solid #f1f3f4;
            font-size: 13px;
            vertical-align: middle;
        }
        
        /* Header and footer cell styling */
        .source-conversion-responsive thead th,
        .source-conversion-responsive tfoot td {
            padding: 12px 16px;
        }
        
        /* Force footer visibility in all browsers */
        .source-conversion-responsive tfoot tr {
            display: table !important;
            width: 100% !important;
            table-layout: fixed !important;
        }
        
        /* Responsive height adjustment - untuk data sedikit atau kosong */
        .source-conversion-responsive.compact {
            height: auto !important;
            min-height: auto !important;
            max-height: none !important;
        }
        
        .source-conversion-responsive.compact .source-conversion-scroll {
            height: auto !important;
            max-height: none !important;
            overflow: visible !important;
        }
        
        .source-conversion-responsive.compact table {
            height: auto !important;
        }
        
        .source-conversion-responsive.compact tbody {
            overflow: visible !important;
            height: auto !important;
            max-height: none !important;
        }
        
        /* Untuk card dengan data sedikit, pastikan footer tetap sticky tapi tidak ada scroll */
        .source-conversion-responsive.compact tfoot {
            position: relative !important;
            bottom: auto !important;
        }
        
        /* CSS yang lebih spesifik untuk mencegah konflik antar tabel */
        
        /* SOURCE CONVERSION LISTS - compact mode */
        .source-conversion-responsive:has(#source-conversion-table).compact {
            height: auto !important;
            min-height: auto !important;
            max-height: none !important;
        }
        
        .source-conversion-responsive:has(#source-conversion-table).compact .source-conversion-scroll {
            height: auto !important;
            max-height: none !important;
            overflow: visible !important;
        }
        
        .source-conversion-responsive:has(#source-conversion-table).compact tbody {
            overflow: visible !important;
            height: auto !important;
            max-height: none !important;
        }
        
        /* SALES POTENTIAL DEALING BRANCH - compact mode */
        .source-conversion-responsive:has(#potential-branch-table).compact {
            height: auto !important;
            min-height: auto !important;
            max-height: none !important;
        }
        
        .source-conversion-responsive:has(#potential-branch-table).compact .source-conversion-scroll {
            height: auto !important;
            max-height: none !important;
            overflow: visible !important;
        }
        
        .source-conversion-responsive:has(#potential-branch-table).compact tbody {
            overflow: visible !important;
            height: auto !important;
            max-height: none !important;
        }
        
        /* SALES POTENTIAL DEALING LIST - compact mode */
        .source-conversion-responsive:has(#potential-list-table).compact {
            height: auto !important;
            min-height: auto !important;
            max-height: none !important;
        }
        
        .source-conversion-responsive:has(#potential-list-table).compact .source-conversion-scroll {
            height: auto !important;
            max-height: none !important;
            overflow: visible !important;
        }
        
        .source-conversion-responsive:has(#potential-list-table).compact tbody {
            overflow: visible !important;
            height: auto !important;
            max-height: none !important;
        }
        
        /* Fallback untuk browser yang tidak support :has() selector dan CSS yang lebih spesifik */
        @supports not (selector(:has())) {
            /* SOURCE CONVERSION LISTS */
            #source-conversion-container.compact {
                height: auto !important;
                min-height: auto !important;
                max-height: none !important;
            }
            
            #source-conversion-container.compact .source-conversion-scroll {
                height: auto !important;
                max-height: none !important;
                overflow: visible !important;
            }
            
            /* SALES POTENTIAL DEALING BRANCH */
            #potential-branch-container.compact {
                height: auto !important;
                min-height: auto !important;  
                max-height: none !important;
            }
            
            #potential-branch-container.compact .source-conversion-scroll {
                height: auto !important;
                max-height: none !important;
                overflow: visible !important;
            }
            
            /* SALES POTENTIAL DEALING LIST */
            #potential-list-container.compact {
                height: auto !important;
                min-height: auto !important;
                max-height: none !important;
            }
            
            #potential-list-container.compact .source-conversion-scroll {
                height: auto !important;
                max-height: none !important;
                overflow: visible !important;
            }
        }
        
        /* CSS tambahan yang lebih spesifik untuk mengatasi konflik */
        #source-conversion-container.compact {
            height: auto !important;
            min-height: auto !important;
            max-height: none !important;
        }
        
        #potential-branch-container.compact {
            height: auto !important;
            min-height: auto !important;
            max-height: none !important;
        }
        
        #potential-list-container.compact {
            height: auto !important;
            min-height: auto !important;
            max-height: none !important;
        }
        
        /* Cross-browser scrollbar styling */
        .source-conversion-responsive tbody::-webkit-scrollbar {
            width: 8px;
        }
        
        .source-conversion-responsive tbody::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }
        
        .source-conversion-responsive tbody::-webkit-scrollbar-thumb {
            background: #115641;
            border-radius: 4px;
        }
        
        .source-conversion-responsive tbody::-webkit-scrollbar-thumb:hover {
            background: #0d4133;
        }
        
        /* Ensure consistent column widths for SOURCE CONVERSION LISTS */
        #source-conversion-table th:nth-child(1),
        #source-conversion-table td:nth-child(1) { width: 20%; }
        #source-conversion-table th:nth-child(2),
        #source-conversion-table td:nth-child(2) { width: 13%; }
        #source-conversion-table th:nth-child(3),
        #source-conversion-table td:nth-child(3) { width: 13%; }
        #source-conversion-table th:nth-child(4),
        #source-conversion-table td:nth-child(4) { width: 13%; }
        #source-conversion-table th:nth-child(5),
        #source-conversion-table td:nth-child(5) { width: 13%; }
        #source-conversion-table th:nth-child(6),
        #source-conversion-table td:nth-child(6) { width: 13%; }
        #source-conversion-table th:nth-child(7),
        #source-conversion-table td:nth-child(7) { width: 15%; }

        /* Column widths for SALES POTENTIAL DEALING BRANCH (6 columns) */
        #potential-branch-table th:nth-child(1),
        #potential-branch-table td:nth-child(1) { width: 25%; } /* Nama Sales */
        #potential-branch-table th:nth-child(2),
        #potential-branch-table td:nth-child(2) { width: 18%; } /* Warm + Hot Amount */
        #potential-branch-table th:nth-child(3),
        #potential-branch-table td:nth-child(3) { width: 15%; } /* Qty (W + H) */
        #potential-branch-table th:nth-child(4),
        #potential-branch-table td:nth-child(4) { width: 15%; } /* Avg Discount */
        #potential-branch-table th:nth-child(5),
        #potential-branch-table td:nth-child(5) { width: 15%; } /* Branch */
        #potential-branch-table th:nth-child(6),
        #potential-branch-table td:nth-child(6) { width: 12%; } /* Periode */

        /* Column widths for SALES POTENTIAL DEALING LIST (7 columns) */
        #potential-list-table th:nth-child(1),
        #potential-list-table td:nth-child(1) { width: 20%; } /* Nama Customer */
        #potential-list-table th:nth-child(2),
        #potential-list-table td:nth-child(2) { width: 12%; } /* Status */
        #potential-list-table th:nth-child(3),
        #potential-list-table td:nth-child(3) { width: 15%; } /* Amount */
        #potential-list-table th:nth-child(4),
        #potential-list-table td:nth-child(4) { width: 12%; } /* Regional */
        #potential-list-table th:nth-child(5),
        #potential-list-table td:nth-child(5) { width: 12%; } /* Product */
        #potential-list-table th:nth-child(6),
        #potential-list-table td:nth-child(6) { width: 14%; } /* Last Activity */
        #potential-list-table th:nth-child(7),
        #potential-list-table td:nth-child(7) { width: 15%; } /* Data Validation */



        /* Sticky Footer for Total Row */
        .table-with-sticky-footer {
            position: relative;
            background: white;
            border-radius: 0;
            border: 1px solid #e3e6f0;
        }

        .table-body-scroll {
            position: relative;
            overflow-y: auto;
            overflow-x: visible;
        }

        .table-body-scroll::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .table-body-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .table-body-scroll::-webkit-scrollbar-thumb {
            background: #115641;
            border-radius: 4px;
        }

        .table-body-scroll::-webkit-scrollbar-thumb:hover {
            background: #0d4133;
        }

        /* Critical CSS for Chrome sticky footer compatibility */
        .table-body-scroll {
            /* Create stacking context for sticky positioning */
            position: relative;
            z-index: 1;
            /* Force hardware acceleration */
            -webkit-transform: translateZ(0);
            transform: translateZ(0);
            /* Optimize for scroll performance */
            -webkit-overflow-scrolling: touch;
        }

        .table-footer-sticky {
            background: #115641;
            position: sticky;
            bottom: 0;
            z-index: 15;
            border-top: 2px solid #0d4133;
            border-radius: 0 !important;
        }

        .table-footer-sticky td {
            background: #115641 !important;
            color: white !important;
            font-weight: bold !important;
            padding: 12px 16px !important;
            border: none !important;
            font-size: 15px !important;
        }

        /* Specific styling for Source Monitoring List - compact design */
        .table-footer-sticky.source-monitoring-footer {
            background: #115641;
            border-radius: 0;
        }

        .table-footer-sticky.source-monitoring-footer td {
            background: #115641 !important;
            color: white !important;
            font-weight: bold !important;
            padding: 8px 4px !important;
            border: none !important;
            font-size: 10px !important;
            line-height: 1.2 !important;
            text-align: center !important;
            vertical-align: middle !important;
        }

        .table-footer-sticky.source-monitoring-footer td:first-child {
            text-align: left !important;
            padding-left: 8px !important;
        }

        .table-body-scroll table {
            margin-bottom: 0;
        }

        /* Ensure last row has proper spacing */
        .table-body-scroll tbody tr:last-child td {
            border-bottom: 1px solid #f1f3f4;
        }

        /* Source Link Styling */
        .source-link {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .source-link:hover {
            color: #0056b3;
            text-decoration: none;
        }

        /* Source Monitoring Table Styling */
        #source-monitoring-table {
            border-radius: 20px;
            overflow: hidden;
        }

        #source-monitoring-table thead th {
            border: none;
            font-size: 14px;
        }

        #source-monitoring-table tbody td {
            border-top: 1px solid #f1f3f4;
            padding: 16px;
            font-size: 14px;
            vertical-align: middle;
        }

        #source-monitoring-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Responsive adjustments for max 5 rows */
        @media (max-width: 768px) {
            #source-conversion-table thead th,
            #source-conversion-table tbody td {
                padding: 12px 8px;
                font-size: 13px;
            }
            
            .source-badge {
                font-size: 11px;
                padding: 3px 8px;
            }
            
            /* Adjust container height for mobile */
            .source-conversion-table-container {
                max-height: 300px;
            }

            /* Reset source filter width for mobile */
            #source-filter,
            #source-monitoring-source-filter {
                max-width: 100%;
            }
        }
        
        @media (max-width: 576px) {
            #source-conversion-table thead th,
            #source-conversion-table tbody td {
                padding: 10px 6px;
                font-size: 12px;
            }
            
            .source-badge {
                font-size: 10px;
                padding: 2px 6px;
            }
            
            /* Further adjust container height for small mobile */
            .source-conversion-table-container {
                max-height: 280px;
            }

            /* Stack filters vertically on small screens */
            .source-conversion-section .row {
                flex-direction: column;
            }

            .source-conversion-section .col-lg-2,
            .source-conversion-section .col-lg-3,
            .source-conversion-section .col-md-6 {
                width: 100%;
                margin-bottom: 8px;
            }
        }

        .source-badge {
            background: #e3f2fd;
            color: #1976d2;
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 500;
        }

        .conversion-number {
            font-weight: 600;
            color: #115641;
        }

        .cumulative-percentage {
            color: #6c757d !important;
            font-weight: 400;
            font-size: 0.9em;
        }

        /* Source Monitoring Compact Table Styling */
        #source-monitoring-table {
            font-size: 10px;
            background-color: transparent;
            width: 100%;
            table-layout: fixed;
        }

        #source-monitoring-table th {
            white-space: nowrap;
            padding: 8px 4px !important;
            vertical-align: middle;
            border: none;
            text-overflow: ellipsis;
            overflow: hidden;
        }

        #source-monitoring-table td {
            white-space: nowrap;
            padding: 6px 4px !important;
            vertical-align: middle;
            border-top: 1px solid #f1f3f4;
            text-overflow: ellipsis;
            overflow: hidden;
        }

        #source-monitoring-table tbody tr:hover {
            background-color: rgba(248, 249, 250, 0.5);
        }

        /* Specific width for Source column */
        #source-monitoring-table th:first-child,
        #source-monitoring-table td:first-child {
            min-width: 120px;
            max-width: 120px;
        }

        /* Specific width for month columns */
        #source-monitoring-table th:not(:first-child):not(:last-child),
        #source-monitoring-table td:not(:first-child):not(:last-child) {
            width: 45px;
            min-width: 45px;
            max-width: 45px;
        }

        /* Specific width for Total column */
        #source-monitoring-table th:last-child,
        #source-monitoring-table td:last-child {
            min-width: 60px;
            max-width: 60px;
        }

        /* Table container styling - now inside chart-card */
        .source-monitoring-table-container {
            background-color: transparent;
            border: none;
            border-radius: 12px;
            box-shadow: none;
            overflow: hidden;
        }

        /* Responsive Source Monitoring */
        @media (max-width: 1199px) {
            #source-monitoring-table {
                font-size: 9px !important;
            }
            
            #source-monitoring-table th,
            #source-monitoring-table td {
                padding: 6px 3px !important;
            }
            
            #source-monitoring-table th:first-child,
            #source-monitoring-table td:first-child {
                min-width: 100px;
                max-width: 100px;
            }
            
            #source-monitoring-table th:not(:first-child):not(:last-child),
            #source-monitoring-table td:not(:first-child):not(:last-child) {
                width: 40px;
                min-width: 40px;
                max-width: 40px;
            }
            
            .chart-container,
            .table-responsive {
                height: 320px !important;
            }
            
            .chart-title {
                font-size: 16px !important;
            }
            
            .chart-subtitle {
                font-size: 11px !important;
            }
        }

        @media (max-width: 991px) {
            #source-monitoring-table {
                font-size: 8px !important;
            }
            
            #source-monitoring-table th,
            #source-monitoring-table td {
                padding: 5px 2px !important;
            }
            
            #source-monitoring-table th:first-child,
            #source-monitoring-table td:first-child {
                min-width: 80px;
                max-width: 80px;
            }
            
            #source-monitoring-table th:not(:first-child):not(:last-child),
            #source-monitoring-table td:not(:first-child):not(:last-child) {
                width: 35px;
                min-width: 35px;
                max-width: 35px;
            }
            
            #source-monitoring-table th:last-child,
            #source-monitoring-table td:last-child {
                min-width: 50px;
                max-width: 50px;
            }
            
            .chart-container,
            .table-responsive {
                height: 280px !important;
            }
            
            .chart-title {
                font-size: 15px !important;
            }
            
            .chart-subtitle {
                font-size: 10px !important;
            }
        }

        @media (max-width: 767px) {
            /* Stack Source Monitoring components vertically on mobile */
            .source-monitoring-mobile-stack .col-lg-6 {
                margin-bottom: 1rem !important;
            }
            
            #source-monitoring-table {
                font-size: 7px !important;
                min-width: 600px !important;
            }
            
            #source-monitoring-table th,
            #source-monitoring-table td {
                padding: 4px 1px !important;
            }
            
            #source-monitoring-table th:first-child,
            #source-monitoring-table td:first-child {
                min-width: 70px;
                max-width: 70px;
            }
            
            #source-monitoring-table th:not(:first-child):not(:last-child),
            #source-monitoring-table td:not(:first-child):not(:last-child) {
                width: 30px;
                min-width: 30px;
                max-width: 30px;
            }
            
            #source-monitoring-table th:last-child,
            #source-monitoring-table td:last-child {
                min-width: 40px;
                max-width: 40px;
            }
            
            .chart-container,
            .table-responsive {
                height: 250px !important;
            }
            
            .chart-title {
                font-size: 14px !important;
            }
            
            .chart-subtitle {
                font-size: 9px !important;
            }
        }

        @media (max-width: 1199px) {
            .chart-title {
                font-size: 17px;
            }
            
            .chart-subtitle {
                font-size: 12px;
            }
            
            .modern-select,
            .modern-input {
                min-width: 110px;
                padding: 7px 10px;
                font-size: 12px;
                height: 36px; /* Consistent height for tablet */
            }
            
            .modern-apply-btn {
                padding: 7px 16px;
                font-size: 12px;
                min-width: 70px;
                height: 36px; /* Match input/select height for tablet */
            }
        }

        @media (max-width: 991px) {
            .chart-title {
                font-size: 16px;
            }
            
            .chart-subtitle {
                font-size: 11px;
            }
            
            .chart-controls {
                gap: 8px;
            }
            
            .modern-select,
            .modern-input {
                min-width: 100px;
                padding: 6px 8px;
                font-size: 11px;
                height: 32px; /* Consistent height for mobile */
            }
            
            .modern-apply-btn {
                padding: 6px 14px;
                font-size: 11px;
                height: 32px; /* Match input/select height for mobile */
                min-width: 60px;
            }
        }

        @media (max-width: 575px) {
            .chart-controls {
                justify-content: center;
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .control-item {
                flex: 1 1 auto;
                min-width: 0;
            }
            
            .modern-select,
            .modern-input {
                width: 100%;
                min-width: 0;
                height: 32px; /* Consistent height for small screens */
            }
            
            .modern-apply-btn {
                width: 100%;
                min-width: 0;
                height: 32px; /* Match input/select height for small screens */
            }
        }

        /* Dashboard Section Headers Styling */
        .dashboard-section-header {
            background: #115641 !important;
            color: white !important;
            padding: 15px 25px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px !important;
            box-shadow: 0 4px 8px rgba(17, 86, 65, 0.2);
            transition: all 0.3s ease;
        }

        .dashboard-section-header:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 12px rgba(17, 86, 65, 0.3);
        }

        .dashboard-section-header h2 {
            margin: 0 !important;
            font-size: 28px !important;
            font-weight: bold !important;
            color: white !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        /* Responsive Dashboard Headers */
        @media (max-width: 768px) {
            .dashboard-section-header {
                padding: 12px 20px;
                margin-bottom: 15px !important;
            }
            
            .dashboard-section-header h2 {
                font-size: 24px !important;
            }
        }

        @media (max-width: 576px) {
            .dashboard-section-header {
                padding: 10px 15px;
                margin-bottom: 15px !important;
            }
            
            .dashboard-section-header h2 {
                font-size: 20px !important;
            }
        }

        /* Achievement Cards Styling */
        .achievement-card {
            background: #fff;
            border-radius: 20px;
            border: 1px solid #e3e6f0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            height: 380px;
            overflow: hidden;
        }

        .achievement-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .achievement-header {
            background: #f8f9fa;
            padding: 20px 24px;
            border-bottom: 1px solid #e3e6f0;
        }

        .achievement-title {
            font-size: 18px;
            font-weight: 600;
            color: #115641;
            margin: 0;
            text-align: center;
        }

        .achievement-body {
            padding: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: calc(100% - 70px);
        }

        .donut-container {
            width: 200px;
            height: 200px;
            margin-bottom: 20px;
            position: relative;
        }

        .achievement-stats {
            text-align: center;
            width: 100%;
        }

        .stat-item {
            display: block;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 14px;
            color: #6c757d;
            font-weight: 500;
        }

        .stat-value {
            font-size: 16px;
            font-weight: 600;
            color: #115641;
        }

        .stat-separator {
            font-size: 14px;
            color: #6c757d;
            margin: 0 4px;
        }

        .stat-target {
            font-size: 14px;
            color: #6c757d;
            font-weight: 500;
        }

        /* Branch List Container for Super Admin */
        .branch-list-container {
            width: 100%;
            max-height: 290px;
            overflow-y: auto;
            padding: 0;
        }

        .branch-item {
            padding: 18px 20px;
            border-bottom: 1px solid #f1f3f4;
            transition: background-color 0.2s ease;
        }

        .branch-item:last-child {
            border-bottom: none;
        }

        .branch-item:hover {
            background-color: #f8f9fa;
        }

        .branch-info {
            width: 100%;
        }

        .branch-name {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #115641;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .branch-stats {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .branch-target {
            font-size: 12px;
            color: #6c757d;
            font-weight: 500;
        }

        .branch-achievement {
            font-size: 12px;
            color: #115641;
            font-weight: 600;
        }

        /* Progress Bar Styling */
        .branch-progress {
            margin-top: 8px;
        }

        .progress-bar-container {
            width: 100%;
            height: 8px;
            background-color: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 4px;
        }

        .progress-bar-fill {
            height: 100%;
            background-color: #115641;
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .progress-percentage {
            font-size: 11px;
            color: #115641;
            font-weight: 600;
            text-align: right;
        }

        /* Custom scrollbar for branch list */
        .branch-list-container::-webkit-scrollbar {
            width: 4px;
        }

        .branch-list-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 2px;
        }

        .branch-list-container::-webkit-scrollbar-thumb {
            background: #115641;
            border-radius: 2px;
        }

        .branch-list-container::-webkit-scrollbar-thumb:hover {
            background: #0d4133;
        }

        /* Responsive Achievement Cards */
        @media (max-width: 1199px) {
            .achievement-card {
                height: 350px;
            }
            
            .donut-container {
                width: 180px;
                height: 180px;
            }
            
            .achievement-title {
                font-size: 16px;
            }
            
            .stat-value {
                font-size: 15px;
            }
        }

        @media (max-width: 991px) {
            .achievement-card {
                height: 320px;
                margin-bottom: 20px;
            }
            
            .donut-container {
                width: 160px;
                height: 160px;
                margin-bottom: 15px;
            }
            
            .achievement-header {
                padding: 16px 20px;
            }
            
            .achievement-body {
                padding: 20px;
            }
            
            .achievement-title {
                font-size: 15px;
            }
            
            .stat-value {
                font-size: 14px;
            }
            
            .branch-item {
                padding: 12px 16px;
            }
            
            .branch-name {
                font-size: 13px;
            }
            
            .branch-target,
            .branch-achievement {
                font-size: 11px;
            }
            
            .branch-list-container {
                max-height: 240px;
            }
            
            .progress-bar-container {
                height: 6px;
            }
            
            .progress-percentage {
                font-size: 10px;
            }
        }

        @media (max-width: 767px) {
            .achievement-card {
                height: auto;
                min-height: 300px;
            }
            
            .donut-container {
                width: 150px;
                height: 150px;
            }
            
            .achievement-body {
                height: auto;
                min-height: 220px;
            }
        }

        @media (max-width: 576px) {
            .achievement-card {
                margin-bottom: 15px;
            }
            
            .donut-container {
                width: 140px;
                height: 140px;
            }
            
            .achievement-header {
                padding: 12px 16px;
            }
            
            .achievement-body {
                padding: 16px;
                min-height: 200px;
            }
            
            .achievement-title {
                font-size: 14px;
            }
            
            .stat-value {
                font-size: 13px;
            }
            
            .stat-label,
            .stat-target {
                font-size: 12px;
            }
            
            /* Mobile specific adjustments */
            .d-flex.flex-column.flex-sm-row {
                flex-direction: column !important;
            }
            
            .d-flex.flex-column.flex-lg-row {
                align-items: stretch !important;
            }
            
            .modern-input {
                min-width: 100% !important;
                width: 100% !important;
            }
            
            .modern-apply-btn {
                width: 100%;
            }
        }

        /* Additional responsive adjustments for title and controls */
        @media (max-width: 991px) {
            .d-flex.flex-column.flex-lg-row h4 {
                font-size: 20px !important;
                margin-bottom: 0;
            }
        }

        @media (max-width: 767px) {
            .d-flex.flex-column.flex-lg-row h4 {
                font-size: 18px !important;
                text-align: center;
            }
            
            .d-flex.flex-column.flex-lg-row {
                text-align: center;
            }
        }

        /* SLS Dealing Styling */
        .sls-dealing-mobile-stack {
            /* Same styling as source-monitoring-mobile-stack */
        }

        /* SLS Dealing Table Styling */
        #sls-dealing-table {
            font-size: 10px;
            background-color: transparent;
            width: 100%;
            table-layout: fixed;
            border-radius: 0 !important;
        }

        #sls-dealing-table th {
            white-space: nowrap;
            padding: 8px 4px !important;
            vertical-align: middle;
            border: none;
            text-overflow: ellipsis;
            overflow: hidden;
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #115641 !important;
        }

        #sls-dealing-table td {
            white-space: nowrap;
            padding: 6px 4px !important;
            vertical-align: middle;
            border-top: 1px solid #f1f3f4;
            text-overflow: ellipsis;
            overflow: hidden;
        }

        #sls-dealing-table tbody tr:hover {
            background-color: rgba(248, 249, 250, 0.5);
        }

        /* Remove border-radius from table container */
        .table-with-sticky-footer {
            border-radius: 0 !important;
            position: relative;
        }

        .table-body-scroll {
            border-radius: 0 !important;
        }

        /* Remove border-radius from all table elements */
        .table, .table thead, .table tbody, .table tfoot, 
        .table th, .table td, .table tr {
            border-radius: 0 !important;
        }

        /* Source Monitoring Table Sticky Header */
        #source-monitoring-table thead {
            position: sticky;
            top: 0;
            z-index: 20;
            border-radius: 0 !important;
            background-color: #115641;
        }

        #source-monitoring-table thead th {
            position: sticky;
            top: 0;
            z-index: 20;
            background-color: #115641 !important;
            border-radius: 0 !important;
        }

        /* Source Monitoring Table Sticky Footer - CRITICAL FOR CHROME */
        #source-monitoring-table tfoot {
            position: sticky;
            bottom: 0;
            z-index: 15;
            background-color: #115641 !important;
        }

        #source-monitoring-table tfoot td {
            position: sticky;
            bottom: 0;
            z-index: 15;
            background-color: #115641 !important;
            color: white !important;
            font-weight: bold !important;
            border: none !important;
        }

        /* Source Conversion Table Sticky Header */
        #source-conversion-table thead {
            position: sticky;
            top: 0;
            z-index: 20;
            border-radius: 0 !important;
            background-color: #115641;
        }

        #source-conversion-table thead th {
            position: sticky;
            top: 0;
            z-index: 20;
            background-color: #115641 !important;
            border-radius: 0 !important;
        }

        /* Ensure Source Conversion table columns have consistent width */
        #source-conversion-table,
        .table-footer-sticky {
            table-layout: fixed;
            width: 100%;
        }

        #source-conversion-table th:nth-child(1),
        .table-footer-sticky td:nth-child(1) { width: 20%; }
        #source-conversion-table th:nth-child(2),
        .table-footer-sticky td:nth-child(2) { width: 13%; }
        #source-conversion-table th:nth-child(3),
        .table-footer-sticky td:nth-child(3) { width: 13%; }
        #source-conversion-table th:nth-child(4),
        .table-footer-sticky td:nth-child(4) { width: 13%; }
        #source-conversion-table th:nth-child(5),
        .table-footer-sticky td:nth-child(5) { width: 13%; }
        #source-conversion-table th:nth-child(6),
        .table-footer-sticky td:nth-child(6) { width: 13%; }
        #source-conversion-table th:nth-child(7),
        .table-footer-sticky td:nth-child(7) { width: 15%; }

        /* Ensure Source Monitoring and SLS Dealing tables alignment */
        #source-monitoring-table,
        #sls-dealing-table,
        #source-monitoring-table + .table-footer-sticky,
        #sls-dealing-table + .table-footer-sticky {
            table-layout: fixed;
            width: 100%;
        }

        /* Force consistent column alignment */
        .table-footer-sticky td {
            vertical-align: middle;
            text-align: center !important;
            padding: 8px 4px !important;
        }

        /* Override for first column (TOTAL) */
        .table-footer-sticky td:first-child {
            text-align: left !important;
            padding-left: 8px !important;
        }

        /* Force Hardware Acceleration and Chrome Compatibility for Sticky Elements */
        #source-monitoring-table tfoot,
        #sls-dealing-table tfoot,
        #source-monitoring-table tfoot td,
        #sls-dealing-table tfoot td {
            -webkit-transform: translateZ(0) !important;
            -moz-transform: translateZ(0) !important;
            -ms-transform: translateZ(0) !important;
            transform: translateZ(0) !important;
            -webkit-backface-visibility: hidden !important;
            backface-visibility: hidden !important;
            will-change: transform !important;
        }

        /* Additional Chrome-specific fixes for table containers */
        .table-with-sticky-footer {
            -webkit-overflow-scrolling: touch;
            contain: layout style paint;
        }

        /* Integrated Footer Styling - Enhanced for Chrome Compatibility */
        #source-monitoring-table tfoot,
        #sls-dealing-table tfoot {
            background-color: #115641 !important;
            position: -webkit-sticky !important; /* Safari */
            position: sticky !important;
            bottom: 0 !important;
            z-index: 15 !important;
        }

        #source-monitoring-table tfoot td,
        #sls-dealing-table tfoot td {
            background-color: #115641 !important;
            color: white !important;
            font-weight: bold !important;
            border: none !important;
            position: -webkit-sticky !important; /* Safari */
            position: sticky !important;
            bottom: 0 !important;
            z-index: 15 !important;
        }

        /* First column alignment */
        #source-monitoring-table tfoot td:first-child,
        #sls-dealing-table tfoot td:first-child {
            text-align: left !important;
        }

        /* Other columns center alignment */
        #source-monitoring-table tfoot td:not(:first-child),
        #sls-dealing-table tfoot td:not(:first-child) {
            text-align: center !important;
        }

        /* SLS Potential Dealing Tables Styling */
        #potential-branch-table thead,
        #potential-dealing-table thead {
            position: sticky;
            top: 0;
            z-index: 20;
            border-radius: 0 !important;
            background-color: #115641;
        }

        #potential-branch-table thead th,
        #potential-dealing-table thead th {
            position: sticky;
            top: 0;
            z-index: 20;
            background-color: #115641 !important;
            border-radius: 0 !important;
        }

        #potential-branch-table tfoot,
        #potential-dealing-table tfoot {
            background-color: #115641 !important;
            position: sticky;
            bottom: 0;
            z-index: 15;
        }

        #potential-branch-table tfoot td,
        #potential-dealing-table tfoot td {
            background-color: #115641 !important;
            color: white !important;
            font-weight: bold !important;
            border: none !important;
            position: sticky;
            bottom: 0;
        }

        #potential-branch-table tfoot td:first-child,
        #potential-dealing-table tfoot td:first-child {
            text-align: left !important;
        }

        #potential-branch-table tfoot td:not(:first-child),
        #potential-dealing-table tfoot td:not(:first-child) {
            text-align: center !important;
        }

        /* Potential Dealing Controls Alignment */
        .chart-controls {
            align-items: center !important;
        }

        .chart-controls .control-item {
            display: flex;
            align-items: center;
            min-height: 38px;
        }

        .chart-controls .modern-select,
        .chart-controls .modern-input,
        .chart-controls .modern-apply-btn {
            height: 38px;
            min-width: auto;
        }

        /* Ensure table headers don't break awkwardly */
        #potential-branch-table th,
        #potential-dealing-table th,
        #potential-list-table th {
            line-height: 1.2;
            padding-top: 8px !important;
            padding-bottom: 8px !important;
        }

        /* Perfect alignment for Potential Dealing tables */
        #potential-branch-table,
        #potential-list-table {
            table-layout: fixed;
            width: 100%;
        }

        #potential-branch-table th,
        #potential-branch-table td,
        #potential-list-table th,
        #potential-list-table td {
            text-overflow: ellipsis;
            overflow: hidden;
            vertical-align: middle;
        }

        #potential-branch-table tfoot,
        #potential-list-table tfoot {
            background-color: #115641 !important;
            position: sticky;
            bottom: 0;
            z-index: 15;
        }

        #potential-branch-table tfoot td,
        #potential-list-table tfoot td {
            background-color: #115641 !important;
            color: white !important;
            font-weight: bold !important;
            border: none !important;
            position: sticky;
            bottom: 0;
        }

        #potential-branch-table tfoot td:first-child,
        #potential-list-table tfoot td:first-child {
            text-align: left !important;
        }

        #potential-branch-table tfoot td:not(:first-child),
        #potential-list-table tfoot td:not(:first-child) {
            text-align: center !important;
        }

        /* Fully responsive height for tables - adapts to content */
        .potential-dealing-responsive {
            min-height: auto;
            max-height: 500px;
            height: auto !important;
            background: white;
            border-radius: 0;
            border: 1px solid #e3e6f0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .potential-dealing-scroll {
            max-height: 400px;
            overflow-y: auto;
            overflow-x: visible;
            flex: 1;
            min-height: fit-content;
        }

        .potential-dealing-scroll::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .potential-dealing-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .potential-dealing-scroll::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }

        .potential-dealing-scroll::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Header, Body, and Footer consistency */
        #potential-branch-table thead th,
        #potential-list-table thead th {
            padding: 12px 16px !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            line-height: 1.4 !important;
        }

        #potential-branch-table tbody td,
        #potential-list-table tbody td {
            padding: 12px 16px !important;
            font-size: 13px !important;
            line-height: 1.4 !important;
        }

        #potential-branch-table tfoot td,
        #potential-list-table tfoot td {
            padding: 12px 16px !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            line-height: 1.4 !important;
        }

        /* Responsive table behavior */
        .potential-dealing-responsive {
            transition: height 0.3s ease;
        }

        /* Optimize for small data sets */
        #potential-branch-table tr,
        #potential-list-table tr,
        #source-conversion-table tr {
            height: auto;
            min-height: 45px;
        }

        /* Compact mode for tables with few rows */
        .potential-dealing-responsive:has(tbody tr:only-child),
        .potential-dealing-responsive:has(tbody tr:nth-child(-n+2)) {
            min-height: auto;
        }

        /* Ensure loading and empty states are compact */
        .potential-dealing-responsive .text-center.py-5 {
            padding: 2rem 1rem !important;
        }

        .potential-dealing-responsive .text-center.py-4 {
            padding: 1.5rem 1rem !important;
        }

        /* Apply same styling to SOURCE CONVERSION LISTS */
        #source-conversion-table {
            table-layout: fixed;
            width: 100%;
        }

        #source-conversion-table th,
        #source-conversion-table td {
            text-overflow: ellipsis;
            overflow: hidden;
            vertical-align: middle;
        }

        #source-conversion-table thead th {
            padding: 12px 16px !important;
            font-size: 13px !important;
            font-weight: 600 !important;
            line-height: 1.4 !important;
        }

        #source-conversion-table tbody td {
            padding: 12px 16px !important;
            font-size: 13px !important;
            line-height: 1.4 !important;
        }

        #source-conversion-table tfoot {
            background-color: #115641 !important;
            position: sticky;
            bottom: 0;
            z-index: 15;
        }

        #source-conversion-table tfoot td {
            background-color: #115641 !important;
            color: white !important;
            font-weight: bold !important;
            border: none !important;
            position: sticky;
            bottom: 0;
            padding: 12px 16px !important;
            font-size: 13px !important;
            line-height: 1.4 !important;
        }

        #source-conversion-table tfoot td:first-child {
            text-align: left !important;
        }

        #source-conversion-table tfoot td:not(:first-child) {
            text-align: center !important;
        }

        #source-conversion-table tr {
            height: auto;
            min-height: 45px;
        }

        /* Smart height adjustment based on content */
        .potential-dealing-responsive table {
            height: auto;
            min-height: fit-content;
        }

        .potential-dealing-responsive thead {
            flex-shrink: 0;
        }

        .potential-dealing-responsive tfoot {
            flex-shrink: 0;
        }

        .potential-dealing-responsive tbody {
            flex: 1 1 auto;
            min-height: fit-content;
        }

        /* Override any conflicting SOURCE CONVERSION styles */
        #source-conversion-table .py-3 {
            padding-top: 12px !important;
            padding-bottom: 12px !important;
        }

        #source-conversion-table .px-4 {
            padding-left: 16px !important;
            padding-right: 16px !important;
        }

        /* Ensure consistent text alignment */
        #source-conversion-table .text-center {
            text-align: center !important;
        }

        /* Ensure sticky header works properly */
        #sls-dealing-table thead {
            position: sticky;
            top: 0;
            z-index: 20;
            border-radius: 0 !important;
            background-color: #115641;
        }

        #sls-dealing-table thead th {
            position: sticky;
            top: 0;
            z-index: 20;
            background-color: #115641 !important;
            border-radius: 0 !important;
        }

        #sls-dealing-table thead th:first-child {
            border-top-left-radius: 0 !important;
        }

        #sls-dealing-table thead th:last-child {
            border-top-right-radius: 0 !important;
        }

        /* Remove border-radius from sticky footer */
        .table-footer-sticky.source-monitoring-footer {
            border-radius: 0 !important;
        }

        /* Responsive SLS Dealing */
        @media (max-width: 1199px) {
            #sls-dealing-table {
                font-size: 9px !important;
            }
            
            #sls-dealing-table th,
            #sls-dealing-table td {
                padding: 6px 3px !important;
            }
        }

        @media (max-width: 991px) {
            #sls-dealing-table {
                font-size: 8px !important;
            }
            
            #sls-dealing-table th,
            #sls-dealing-table td {
                padding: 5px 2px !important;
            }
        }

        @media (max-width: 767px) {
            /* Stack SLS Dealing components vertically on mobile */
            .sls-dealing-mobile-stack .col-lg-6 {
                margin-bottom: 1rem !important;
            }
            
            #sls-dealing-table {
                font-size: 7px !important;
                min-width: 800px !important;
            }
            
            #sls-dealing-table th,
            #sls-dealing-table td {
                padding: 4px 1px !important;
            }
        }
    </style>
@endsection

@section('content')
    {{-- <h1 class="h3 mb-4 text-gray-800">Dashboard</h1> --}}

    {{-- MARKETING DASHBOARD HEADER --}}
    <div class="col-md-12 mb-4">
        <div class="dashboard-section-header" style="background: #115641; color: white; padding: 15px 25px; border-radius: 10px; text-align: center; margin-bottom: 20px;">
            <h2 class="mb-0" style="font-size: 28px; font-weight: bold;">MARKETING DASHBOARD</h2>
        </div>
    </div>

    <div class="col-md-12 mb-4">
        <h2 class="font-weight-bold mb-4" style="font-size: 30px; color: #115641;">PROCESS FLOW</h2>
        
        {{-- Row 1 - Qty & ATR Time (5 Cards) --}}
        <div class="row" id="process-flow-container">
            {{-- All Leads In --}}
            <div class="col-xl-2-4 col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="process-flow-card">
                    <div class="process-flow-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="process-flow-content">
                        <div class="process-flow-title">All Leads In</div>
                        <div class="process-flow-count" id="all-leads-qty">-</div>
                        {{-- <div class="process-flow-atr" id="all-leads-time">Loading...</div> --}}
                    </div>
                </div>
            </div>
            
            {{-- Acquisition --}}
            <div class="col-xl-2-4 col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="process-flow-card">
                    <div class="process-flow-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="process-flow-content">
                        <div class="process-flow-title">Acquisition</div>
                        <div class="process-flow-count" id="acquisition-qty">-</div>
                        <div class="process-flow-atr" id="acquisition-time">Loading...</div>
                    </div>
                </div>
            </div>
            
            {{-- Meeting --}}
            <div class="col-xl-2-4 col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="process-flow-card">
                    <div class="process-flow-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="process-flow-content">
                        <div class="process-flow-title">Meeting</div>
                        <div class="process-flow-count" id="meeting-qty">-</div>
                        <div class="process-flow-atr" id="meeting-time">Loading...</div>
                    </div>
                </div>
            </div>
            
            {{-- Quotation --}}
            <div class="col-xl-2-4 col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="process-flow-card">
                    <div class="process-flow-icon">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div class="process-flow-content">
                        <div class="process-flow-title">Quotation</div>
                        <div class="process-flow-count" id="quotation-qty">-</div>
                        <div class="process-flow-atr" id="quotation-time">Loading...</div>
                    </div>
                </div>
            </div>
            
            {{-- Invoice --}}
            <div class="col-xl-2-4 col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="process-flow-card">
                    <div class="process-flow-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="process-flow-content">
                        <div class="process-flow-title">Invoice</div>
                        <div class="process-flow-count" id="invoice-qty">-</div>
                        <div class="process-flow-atr" id="invoice-time">Loading...</div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Row 2 - Percentage & Amount Data (5 Cards) --}}
        <div class="row" id="process-flow-row2">
            {{-- All Leads In - Percentage --}}
            <div class="col-xl-2-4 col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="process-flow-card">
                    <div class="process-flow-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="process-flow-content">
                        <div class="process-flow-title">All Leads In %</div>
                        <div class="process-flow-count" id="all-leads-pct">-</div>
                        {{-- <div class="process-flow-atr" id="all-leads-acq-pct">Loading...</div> --}}
                    </div>
                </div>
            </div>
            
            {{-- Acquisition - Percentage --}}
            <div class="col-xl-2-4 col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="process-flow-card">
                    <div class="process-flow-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="process-flow-content">
                        <div class="process-flow-title">Acquisition %</div>
                        <div class="process-flow-count" id="acquisition-pct">-</div>
                        {{-- <div class="process-flow-atr" id="acquisition-cvr">Loading...</div> --}}
                    </div>
                </div>
            </div>
            
            {{-- Meeting - Percentage --}}
            <div class="col-xl-2-4 col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="process-flow-card">
                    <div class="process-flow-icon">
                        <i class="fas fa-percentage"></i>
                    </div>
                    <div class="process-flow-content">
                        <div class="process-flow-title">Meeting %</div>
                        <div class="process-flow-count" id="meeting-pct">-</div>
                        {{-- <div class="process-flow-atr" id="meeting-my">Loading...</div> --}}
                    </div>
                </div>
            </div>
            
            {{-- Quotation - Percentage & Amount --}}
            <div class="col-xl-2-4 col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="process-flow-card">
                    <div class="process-flow-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="process-flow-content">
                        <div class="process-flow-title">Quotation %</div>
                        <div class="process-flow-count" id="quotation-pct">-</div>
                        <div class="process-flow-atr" id="quotation-amount">Loading...</div>
                    </div>
                </div>
            </div>
            
            {{-- Invoice - Percentage & Amount --}}
            <div class="col-xl-2-4 col-lg-4 col-md-6 col-sm-6 col-12">
                <div class="process-flow-card">
                    <div class="process-flow-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="process-flow-content">
                        <div class="process-flow-title">Invoice %</div>
                        <div class="process-flow-count" id="invoice-pct">-</div>
                        <div class="process-flow-atr" id="invoice-amount">Loading...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SOURCE CONVERSION LISTS Section --}}
    <div class="col-md-12 mb-4">
        <h2 class="font-weight-bold mb-4" style="font-size: 30px; color: #115641;">SOURCE CONVERSION LISTS</h2>
        
        <div class="card shadow" style="border-radius: 20px; overflow: hidden;">
            <div class="card-body p-0">
                {{-- Filter Controls --}}
                <div class="p-4 bg-light source-conversion-section">
                    @if(auth()->user()->role?->code === 'super_admin')
                    {{-- Super Admin Layout - Single row with all filters --}}
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <label class="form-label">Branch</label>
                            <select id="source-branch" class="form-select source-control-input">
                                <option value="">All Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <label class="form-label">Source</label>
                            <select id="source-filter" class="form-select source-control-input">
                                <option value="">All Source</option>
                                @foreach($leadSources as $source)
                                    <option value="{{ $source->name }}">{{ $source->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" id="source-start-date" class="form-control source-control-input" value="{{ now()->startOfYear()->format('Y-m-d') }}">
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <label class="form-label">End Date</label>
                            <input type="date" id="source-end-date" class="form-control source-control-input" value="{{ now()->endOfYear()->format('Y-m-d') }}">
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn source-apply-button w-100" id="source-apply">
                                <i class="fas fa-filter me-1"></i> Apply Filter
                            </button>
                        </div>
                    </div>
                    @else
                    {{-- Non-Admin Layout - Single row with all filters --}}
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-3 col-md-3 col-sm-6">
                            <label class="form-label">Source</label>
                            <select id="source-filter" class="form-select source-control-input">
                                <option value="">All Source</option>
                                @foreach($leadSources as $source)
                                    <option value="{{ $source->name }}">{{ $source->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" id="source-start-date" class="form-control source-control-input" value="{{ now()->startOfYear()->format('Y-m-d') }}">
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-6">
                            <label class="form-label">End Date</label>
                            <input type="date" id="source-end-date" class="form-control source-control-input" value="{{ now()->endOfYear()->format('Y-m-d') }}">
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-6">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn source-apply-button w-100" id="source-apply">
                                <i class="fas fa-filter me-1"></i> Apply Filter
                            </button>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Responsive Table with Integrated Footer --}}
                <div class="source-conversion-responsive" id="source-conversion-container">
                    <!-- Scrollable Table Body -->
                    <div class="source-conversion-scroll">
                        <table class="table table-hover mb-0" id="source-conversion-table">
                            <thead style="background-color: #115641; position: sticky; top: 0; z-index: 20;">
                                <tr>
                                    <th class="text-white fw-bold py-3 px-4" style="border-radius: 0; position: sticky; top: 0; background-color: #115641;">Source</th>
                                    <th class="text-white fw-bold py-3 px-4 text-center" style="border-radius: 0; position: sticky; top: 0; background-color: #115641;">Cum</th>
                                    <th class="text-white fw-bold py-3 px-4 text-center" style="border-radius: 0; position: sticky; top: 0; background-color: #115641;">Cold</th>
                                    <th class="text-white fw-bold py-3 px-4 text-center" style="border-radius: 0; position: sticky; top: 0; background-color: #115641;">Warm</th>
                                    <th class="text-white fw-bold py-3 px-4 text-center" style="border-radius: 0; position: sticky; top: 0; background-color: #115641;">Hot</th>
                                    <th class="text-white fw-bold py-3 px-4 text-center" style="border-radius: 0; position: sticky; top: 0; background-color: #115641;">Deal</th>
                                    <th class="text-white fw-bold py-3 px-4 text-center" style="background-color: #0d4534; position: sticky; top: 0; border-radius: 0;">Total</th>
                                </tr>
                            </thead>
                            <tbody id="source-conversion-tbody">
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="text-success">
                                            <div class="spinner-border text-success" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <p class="mt-2 mb-0 text-muted">Loading source conversion data...</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot style="background-color: #115641; position: sticky; bottom: 0; z-index: 15;">
                                <tr id="source-conversion-total-row">
                                    <td class="text-white fw-bold py-3 px-4" style="border-radius: 0;">TOTAL</td>
                                    <td class="text-white fw-bold py-3 px-4 text-center" id="total-cum">0</td>
                                    <td class="text-white fw-bold py-3 px-4 text-center" id="total-cold">0</td>
                                    <td class="text-white fw-bold py-3 px-4 text-center" id="total-warm">0</td>
                                    <td class="text-white fw-bold py-3 px-4 text-center" id="total-hot">0</td>
                                    <td class="text-white fw-bold py-3 px-4 text-center" id="total-deal">0</td>
                                    <td class="text-white fw-bold py-3 px-4 text-center" style="background-color: #0d4534;" id="total-all">0</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SOURCE MONITORING Section -->
    <div class="col-md-12 mb-4">
        <h2 class="font-weight-bold mb-4" style="font-size: 30px; color: #115641;">SOURCE MONITORING</h2>
        
        <!-- Side-by-side Source Monitoring row -->
        <div class="row source-monitoring-mobile-stack">
            <!-- Source Monitoring Chart (Left) -->
            <div class="col-lg-6 col-md-12 mb-4">
                <div class="card chart-card shadow-sm border-0">
                    <div class="card-body p-4">
                        <!-- Chart Title -->
                        <div class="chart-title-section d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h5 class="chart-title mb-0">Source Monitoring Chart</h5>
                                {{-- <p class="chart-subtitle text-muted mb-0">Monthly Lead Source Tracking</p> --}}
                            </div>
                        </div>
                        
                        <!-- Chart Controls -->
                        <div class="chart-controls d-flex flex-wrap gap-2 mb-4">
                            @if(auth()->user()->role?->code === 'super_admin')
                            <div class="control-item">
                                <select id="source-monitoring-branch" class="form-select modern-select">
                                    <option value="">All Branch</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div class="control-item">
                                <input type="number" id="source-monitoring-year" class="form-control modern-input" 
                                       value="{{ now()->year }}" min="2000" max="2100">
                            </div>
                            <div class="control-item">
                                <button type="button" class="btn modern-apply-btn" id="source-monitoring-apply">
                                    Apply
                                </button>
                            </div>
                        </div>

                        <!-- Chart Container -->
                        <div class="chart-container" style="height: 350px; position: relative;">
                            <canvas id="source-monitoring-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Source Monitoring Table (Right) -->
            <div class="col-lg-6 col-md-12 mb-4">
                <div class="card chart-card shadow-sm border-0">
                    <div class="card-body p-4">
                        <!-- Table Title -->
                        <div class="chart-title-section d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h5 class="chart-title mb-0">Source Monitoring List</h5>
                                {{-- <p class="chart-subtitle text-muted mb-0">Monthly Data Summary</p> --}}
                            </div>
                        </div>
                        
                        <!-- Table Controls -->
                        <div class="chart-controls d-flex flex-wrap gap-2 mb-4">
                            @if(auth()->user()->role?->code === 'super_admin')
                            <div class="control-item">
                                <select id="source-monitoring-table-branch" class="form-select modern-select">
                                    <option value="">All Branch</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div class="control-item">
                                <select id="source-monitoring-source-filter" class="form-select modern-select">
                                    <option value="">All Source</option>
                                    @foreach($leadSources as $source)
                                        <option value="{{ $source->name }}">{{ $source->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="control-item">
                                <input type="number" id="source-monitoring-table-year" class="form-control modern-input" 
                                       value="{{ now()->year }}" min="2000" max="2100">
                            </div>
                            <div class="control-item">
                                <button type="button" class="btn modern-apply-btn" id="source-monitoring-table-apply">
                                    Apply
                                </button>
                            </div>
                        </div>

                        {{-- Table with Sticky Footer for Total - Same as SLS Dealing --}}
                        <div class="table-with-sticky-footer" style="height: 350px; overflow: auto; border: 1px solid #e3e6f0; border-radius: 0;">
                            <div style="min-width: 900px;">
                                <!-- Scrollable Table Body -->
                                <div class="table-body-scroll" style="overflow: visible;">
                                    <table class="table table-hover table-sm mb-0" id="source-monitoring-table">
                                        <thead style="background-color: #115641; position: sticky; top: 0; z-index: 10;">
                                            <tr>
                                                <th class="text-white fw-bold py-2 px-2" style="font-size: 11px; min-width: 120px;">Source</th>
                                                <th class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px; width: 45px;">Jan</th>
                                                <th class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px; width: 45px;">Feb</th>
                                                <th class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px; width: 45px;">Mar</th>
                                                <th class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px; width: 45px;">Apr</th>
                                                <th class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px; width: 45px;">May</th>
                                                <th class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px; width: 45px;">Jun</th>
                                                <th class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px; width: 45px;">Jul</th>
                                                <th class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px; width: 45px;">Aug</th>
                                                <th class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px; width: 45px;">Sep</th>
                                                <th class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px; width: 45px;">Oct</th>
                                                <th class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px; width: 45px;">Nov</th>
                                                <th class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px; width: 45px;">Dec</th>
                                                <th class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px; min-width: 60px; background-color: #0d4534;">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody id="source-monitoring-tbody">
                                            <tr>
                                                <td colspan="14" class="text-center py-4">
                                                    <div class="text-success">
                                                        <div class="spinner-border text-success" role="status">
                                                            <span class="visually-hidden">Loading...</span>
                                                        </div>
                                                        <p class="mt-3 mb-0 text-muted">Loading source monitoring data...</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot style="background-color: #115641; position: sticky; bottom: 0; z-index: 15;">
                                            <tr id="source-monitoring-total-row">
                                                <td class="text-white fw-bold py-2 px-2" style="font-size: 11px; border-radius: 0;">TOTAL</td>
                                                <td class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px;" id="total-jan">0</td>
                                                <td class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px;" id="total-feb">0</td>
                                                <td class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px;" id="total-mar">0</td>
                                                <td class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px;" id="total-apr">0</td>
                                                <td class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px;" id="total-may">0</td>
                                                <td class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px;" id="total-jun">0</td>
                                                <td class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px;" id="total-jul">0</td>
                                                <td class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px;" id="total-aug">0</td>
                                                <td class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px;" id="total-sep">0</td>
                                                <td class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px;" id="total-oct">0</td>
                                                <td class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px;" id="total-nov">0</td>
                                                <td class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px;" id="total-dec">0</td>
                                                <td class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px; background-color: #0d4534;" id="total-year">0</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End side-by-side Source Monitoring row -->
    </div>

    
    
{{-- <div class="col-md-12 mb-4">
  <div class="card shadow border-left-info">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">
        Ringkasan Dashboard & Cara Baca
      </h6>
      <button class="btn btn-link collapse-toggle" type="button"
              data-bs-toggle="collapse" data-bs-target="#summaryBody" aria-expanded="true">
        <i class="fas fa-angle-down"></i>
      </button>
    </div>

    <div id="summaryBody" class="collapse show">
      <div class="card-body">
        <p class="small text-muted mb-3">
          Gambaran singkat seluruh laporan di dashboard ini. Gunakan tautan untuk melompat ke bagiannya.
        </p>

        <ul class="list-group list-group-flush">

          <li class="list-group-item">
            <span class="badge badge-pill badge-secondary mr-2">Kartu</span>
            <strong>Status Quotation (Draft/Review/Published/Rejected/Expired)</strong> —
            menampilkan <em>jumlah</em> dokumen dan <em>total nominal</em> per status. Warna border
            mengikuti status untuk memudahkan pemindaian cepat.
          </li>

          <li class="list-group-item">
            <span class="badge badge-pill badge-warning mr-2">Line</span>
            <a href="#tvsmBody" class="font-weight-bold">Target vs Sales (Bulanan - 1 Tahun)</a> —
            perbandingan target dan realisasi penjualan per bulan. Filter:
            <em>scope</em> (Global/Jakarta/Makassar/Surabaya) & <em>tahun</em>. Tooltips menampilkan nilai rupiah.
          </li>

          <li class="list-group-item">
            <span class="badge badge-pill badge-success mr-2">Donut</span>
            <a href="#donutBody" class="font-weight-bold">Sales Achievement vs Target</a> —
            ringkasan pencapaian terhadap target dalam periode terpilih.
            Terdiri dari: <em>Global Achievement</em>, <em>All Branch Target (Plan)</em>, dan
            <em>Achievement per Branch</em>. Caption menampilkan persentase & nominal
            <code>Achieved/Target</code>.
          </li>

          <li class="list-group-item">
            <span class="badge badge-pill badge-primary mr-2">Bar</span>
            <a href="#svtPctBody" class="font-weight-bold">Achievement vs Target per Branch (Monthly %)</a> —
            persentase pencapaian per cabang tiap bulan dalam setahun (filter tahun).
          </li>

          <li class="list-group-item">
            <span class="badge badge-pill badge-info mr-2">Line</span>
            <a href="#ordersMonthlyBody" class="font-weight-bold">Trend Orders Bulanan (YTD)</a> —
            dua seri: <em>Jumlah Order</em> & <em>Nominal Order</em> (sumbu ganda).
            Filter: cabang & rentang tanggal.
          </li>

          <li class="list-group-item">
            <span class="badge badge-pill badge-dark mr-2">Bar + Line</span>
            <a href="#salesPerfBody" class="font-weight-bold">Sales Performance</a> —
            (1) Bar: distribusi Cold/Warm/Hot/Deal per sales; (2) Line: tren %
            achievement untuk Top 3/pilihan sales. Filter: cabang & periode.
          </li>

          <li class="list-group-item">
            <span class="badge badge-pill badge-secondary mr-2">Bar</span>
            <a href="#group3Body" class="font-weight-bold">Lead Overview</a> —
            ringkasan agregat leads pada periode & cabang terpilih (komposisi/fokus funnel).
          </li>

          <li class="list-group-item">
            <span class="badge badge-pill badge-secondary mr-2">Pie</span>
            <strong>Konversi Leads (Cold→Warm & Warm→Hot)</strong> —
            dua pie chart yang menunjukkan rasio konversi antar level kualitas leads.
          </li>

          <li class="list-group-item">
            <span class="badge badge-pill badge-secondary mr-2">Bar</span>
            <a href="#group5Body" class="font-weight-bold">Jumlah Leads Total</a> —
            total leads per status (Cold/Warm/Hot) pada periode & cabang terpilih.
          </li>

          <li class="list-group-item">
            <span class="badge badge-pill badge-secondary mr-2">Bar</span>
            <a href="#group6Body" class="font-weight-bold">Jumlah Quotation</a> —
            total quotation per status (Review/Published/Rejected) dengan filter cabang & tanggal.
          </li>

          <li class="list-group-item">
            <span class="badge badge-pill badge-secondary mr-2">Bar</span>
            <a href="#group7Body" class="font-weight-bold">Leads Berdasarkan Source</a> —
            tiga bagian (Cold/Warm/Hot) yang menampilkan jumlah leads per sumber masuk.
            Filter cabang & periode; cocok untuk evaluasi efektivitas kanal akuisisi.
          </li>

        </ul>

        <hr class="my-3">

        <div class="small text-muted">
          <strong>Tips cepat:</strong>
          <ul class="mb-0 pl-3">
            <li>Pakai tombol <em>Apply</em> di tiap kartu untuk memuat data sesuai filter.</li>
            <li>Tooltip di chart menampilkan nilai & format (Rp/%). Arahkan kursor ke titik/batang.</li>
            <li>Donut menampilkan <em>Achieved</em> vs <em>Remaining</em> dengan caption persentase.</li>
            <li>Grafik Leads per Branch memiliki garis <em>Target</em> (putus-putus) bila tersedia.</li>
          </ul>
        </div>

      </div>
    </div>
  </div>
</div> --}}


    {{-- <div class="col-md-12 mb-2">
        @if ($showOrders)
            @php
                $statusColors = [
                    'draft' => 'secondary',
                    'review' => 'warning',
                    'published' => 'success',
                    'rejected' => 'danger',
                    'expired' => 'dark',
                ];
                $keys = array_keys($quotationStatusStats);
            @endphp

          
            <div class="row justify-content-center">
                @for ($i = 0; $i < 3; $i++)
                    @php
                        $status = $keys[$i];
                        $stats = $quotationStatusStats[$status];
                        $color = $statusColors[$status] ?? 'primary';
                    @endphp
                    <div class="col-md-4 mb-4 d-flex justify-content-center">
                        <div class="card border-left-{{ $color }} shadow h-100 py-2 w-100">
                            <div class="card-body text-center">
                                <div class="text-xs font-weight-bold text-{{ $color }} text-uppercase mb-1">
                                    {{ ucfirst($status) }} Quotations
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total'] }}</div>
                                <div class="text-xs text-gray-700">Rp{{ number_format($stats['amount'], 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>

        
            <div class="row justify-content-center">
                @for ($i = 3; $i < 5; $i++)
                    @php
                        $status = $keys[$i];
                        $stats = $quotationStatusStats[$status];
                        $color = $statusColors[$status] ?? 'primary';
                    @endphp
                    <div class="col-md-6 mb-4 d-flex justify-content-center">
                        <div class="card border-left-{{ $color }} shadow h-100 py-2 w-100">
                            <div class="card-body text-center">
                                <div class="text-xs font-weight-bold text-{{ $color }} text-uppercase mb-1">
                                    {{ ucfirst($status) }} Quotations
                                </div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['total'] }}</div>
                                <div class="text-xs text-gray-700">Rp{{ number_format($stats['amount'], 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    </div>
                @endfor
            </div>
        @endif
    </div> --}}

    {{-- SALES DASHBOARD HEADER --}}
    <div class="col-md-12 mb-4">
        <div class="dashboard-section-header" style="background: #115641; color: white; padding: 15px 25px; border-radius: 10px; text-align: center; margin-bottom: 20px;">
            <h2 class="mb-0" style="font-size: 28px; font-weight: bold;">SALES DASHBOARD</h2>
        </div>
    </div>

    <div class="col-md-12 mb-4">
        <h2 class="font-weight-bold mb-4" style="font-size: 30px; color: #115641;">SALES ACHIEVEMENT VS TARGET</h2>
        
        <div class="card shadow" style="border-radius: 20px; overflow: hidden;">
            <div class="card-body p-0">
                {{-- Filter Controls --}}
                <div class="p-4 bg-light">
                    <div class="row g-3 align-items-end justify-content-start">
                        <div class="col-md-3 ms-auto">
                            <label class="form-label">Start Date</label>
                            <input type="date" id="donut_start" class="form-control source-control-input"
                                   value="{{ $defaultYtdStart }}" onfocus="this.showPicker()">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" id="donut_end" class="form-control source-control-input"
                                   value="{{ $defaultYtdEnd }}" onfocus="this.showPicker()">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn source-apply-button w-100" id="donut_apply">
                                <i class="fas fa-filter me-1"></i> Apply Filter
                            </button>
                        </div>
                    </div>
                </div>
                
                {{-- Main Content --}}
                <div class="p-4">                @if(auth()->user()->role?->code === 'super_admin')
                <!-- Super Admin Layout -->
                <div class="row g-4">
                    <!-- Global Achievement -->
                    <div class="col-lg-4 col-md-6">
                        <div class="achievement-card">
                            <div class="achievement-header">
                                <h5 class="achievement-title">Global Achievement</h5>
                            </div>
                            <div class="achievement-body">
                                <div class="donut-container">
                                    <canvas id="donut_global"></canvas>
                                </div>
                                <div class="achievement-stats">
                                    <div class="stat-item">
                                        <span class="stat-label">Achievement:</span>
                                        <span class="stat-value" id="global_achievement_pct">17%</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-value" id="global_achievement_amount">Rp3.137.440.149</span>
                                        <span class="stat-separator">/</span>
                                        <span class="stat-target" id="global_target_amount">Rp18.150.000.000</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- All Branch Target -->
                    <div class="col-lg-4 col-md-6">
                        <div class="achievement-card">
                            <div class="achievement-header">
                                <h5 class="achievement-title">All Branch Target</h5>
                            </div>
                            <div class="achievement-body">
                                <div class="donut-container">
                                    <canvas id="donut_all"></canvas>
                                </div>
                                <div class="achievement-stats">
                                    <div class="stat-item">
                                        <span class="stat-label">Achievement:</span>
                                        <span class="stat-value" id="all_achievement_pct">17%</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-value" id="all_achievement_amount">Rp3.137.440.149</span>
                                        <span class="stat-separator">/</span>
                                        <span class="stat-target" id="all_target_amount">Rp18.150.000.000</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Achievement per Branch Container -->
                    <div class="col-lg-4 col-md-12">
                        <div class="achievement-card">
                            <div class="achievement-header">
                                <h5 class="achievement-title">Achievement per Branch</h5>
                            </div>
                            <div class="achievement-body" style="height: calc(100% - 70px); padding: 16px;">
                                <div id="branch_achievements_container" class="branch-list-container">
                                    <!-- Branch items will be populated by JavaScript -->
                                    <div class="branch-item">
                                        <div class="branch-info">
                                            <span class="branch-name">BRANCH JAKARTA</span>
                                            <div class="branch-stats">
                                                <span class="branch-target">Target: Rp. 61.813.125.000</span>
                                                <span class="branch-achievement">Achievement: 5% - Rp2.925.728.437</span>
                                            </div>
                                            <div class="branch-progress">
                                                <div class="progress-bar-container">
                                                    <div class="progress-bar-fill" style="width: 5%;"></div>
                                                </div>
                                                <div class="progress-percentage">5%</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="branch-item">
                                        <div class="branch-info">
                                            <span class="branch-name">BRANCH SURABAYA</span>
                                            <div class="branch-stats">
                                                <span class="branch-target">Target: Rp. 48.317.850.000</span>
                                                <span class="branch-achievement">Achievement: 1% - Rp265.031.852</span>
                                            </div>
                                            <div class="branch-progress">
                                                <div class="progress-bar-container">
                                                    <div class="progress-bar-fill" style="width: 1%;"></div>
                                                </div>
                                                <div class="progress-percentage">1%</div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="branch-item">
                                        <div class="branch-info">
                                            <span class="branch-name">BRANCH MAKASSAR</span>
                                            <div class="branch-stats">
                                                <span class="branch-target">Target: Rp. 27.610.200.000</span>
                                                <span class="branch-achievement">Achievement: 0% - Rp739.538</span>
                                            </div>
                                            <div class="branch-progress">
                                                <div class="progress-bar-container">
                                                    <div class="progress-bar-fill" style="width: 0%;"></div>
                                                </div>
                                                <div class="progress-percentage">0%</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <!-- Branch User Layout -->
                <div class="row g-4">
                    <!-- Global Achievement -->
                    <div class="col-lg-4 col-md-6">
                        <div class="achievement-card">
                            <div class="achievement-header">
                                <h5 class="achievement-title">Global Achievement</h5>
                            </div>
                            <div class="achievement-body">
                                <div class="donut-container">
                                    <canvas id="donut_global_branch"></canvas>
                                </div>
                                <div class="achievement-stats">
                                    <div class="stat-item">
                                        <span class="stat-label">Achievement:</span>
                                        <span class="stat-value" id="global_branch_achievement_pct">17%</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-value" id="global_branch_achievement_amount">Rp3.137.440.149</span>
                                        <span class="stat-separator">/</span>
                                        <span class="stat-target" id="global_branch_target_amount">Rp18.150.000.000</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- All Branch Target (Plan) -->
                    <div class="col-lg-4 col-md-6">
                        <div class="achievement-card">
                            <div class="achievement-header">
                                <h5 class="achievement-title">All Branch Target (Plan)</h5>
                            </div>
                            <div class="achievement-body">
                                <div class="donut-container">
                                    <canvas id="donut_all_branch"></canvas>
                                </div>
                                <div class="achievement-stats">
                                    <div class="stat-item">
                                        <span class="stat-label">Achievement:</span>
                                        <span class="stat-value" id="all_branch_achievement_pct">17%</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-value" id="all_branch_achievement_amount">Rp3.137.440.149</span>
                                        <span class="stat-separator">/</span>
                                        <span class="stat-target" id="all_branch_target_amount">Rp18.150.000.000</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Achievement per Branch -->
                    <div class="col-lg-4 col-md-12">
                        <div class="achievement-card">
                            <div class="achievement-header">
                                <h5 class="achievement-title">Achievement per Branch</h5>
                            </div>
                            <div class="achievement-body">
                                <div class="donut-container">
                                    <canvas id="donut_branch_single"></canvas>
                                </div>
                                <div class="achievement-stats">
                                    <div class="stat-item">
                                        <span class="stat-label">Achievement:</span>
                                        <span class="stat-value" id="branch_single_achievement_pct">17%</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-value" id="branch_single_achievement_amount">Rp3.137.440.149</span>
                                        <span class="stat-separator">/</span>
                                        <span class="stat-target" id="branch_single_target_amount">Rp18.150.000.000</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Side-by-side charts section -->
    <div class="col-md-12 mb-4">
        <!-- Side-by-side charts row -->
        <div class="row">
          <!-- Target vs Sales Chart (Left) -->
          <div class="col-lg-6 col-md-12 mb-4">
            <h2 class="font-weight-bold mb-3" style="font-size: 30px; color: #115641;">TARGET VS SALES</h2>
            <div class="card shadow" style="border-radius: 20px; overflow: hidden;">
              <div class="card-body p-4">
            
            <!-- Chart Controls -->
            <div class="chart-controls d-flex flex-wrap gap-2 mb-4">
              <div class="control-item">
                <select id="tvsm_scope" class="form-select modern-select">
                  <option value="global">Global</option>
                  <option value="jakarta">Branch Jakarta</option>
                  <option value="makassar">Branch Makassar</option>
                  <option value="surabaya">Branch Surabaya</option>
                </select>
              </div>
              <div class="control-item">
                <input type="number" id="tvsm_year" class="form-control modern-input" value="{{ now()->year }}" min="2000" max="2100">
              </div>
              <div class="control-item">
                <button type="button" class="btn modern-apply-btn" id="tvsm_apply">
                  Apply
                </button>
              </div>
            </div>

            <!-- Chart Container -->
            <div class="chart-container" style="height: 300px; position: relative;">
              <canvas id="tvsm_chart"></canvas>
            </div>
          </div>
        </div>
      </div>

          <!-- Achievement vs Target Chart (Right) -->
          <div class="col-lg-6 col-md-12 mb-4">
            <h2 class="font-weight-bold mb-3" style="font-size: 30px; color: #115641;">ACHIEVEMENT VS TARGET</h2>
            <div class="card shadow" style="border-radius: 20px; overflow: hidden;">
              <div class="card-body p-4">
            
            <!-- Chart Controls -->
            <div class="chart-controls d-flex flex-wrap gap-2 mb-4">
              <div class="control-item">
                <input type="number" id="svt_year" class="form-control modern-input" value="{{ now()->year }}" min="2000" max="2100">
              </div>
              <div class="control-item">
                <button type="button" class="btn modern-apply-btn" id="svt_apply">
                  Apply
                </button>
              </div>
            </div>

            <!-- Chart Container -->
            <div class="chart-container" style="height: 300px; position: relative;">
              <canvas id="svt_percent_chart"></canvas>
            </div>
            </div>
          </div>
        </div>
        <!-- End side-by-side charts row -->
      </div>
    </div>

    <!-- SLS DEALING Section -->
    <div class="col-md-12 mb-4">
        <h2 class="font-weight-bold mb-4" style="font-size: 30px; color: #115641;">SALES DEALING</h2>
        
        <!-- Side-by-side SLS Dealing row -->
        <div class="row sls-dealing-mobile-stack">
            <!-- SLS Dealing Chart (Left) -->
            <div class="col-lg-6 col-md-12 mb-4">
                <div class="card chart-card shadow-sm border-0">
                    <div class="card-body p-4">
                        <!-- Chart Title -->
                        <div class="chart-title-section d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h5 class="chart-title mb-0">Dealing Chart</h5>
                            </div>
                        </div>
                        
                        <!-- Chart Controls -->
                        <div class="chart-controls d-flex flex-wrap gap-2 mb-4">
                            @if(auth()->user()->role?->code === 'super_admin')
                            <div class="control-item">
                                <select id="sls-dealing-branch" class="form-select modern-select">
                                    <option value="">All Branch</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div class="control-item">
                                <input type="date" id="sls-dealing-start-date" class="form-control modern-input" 
                                       value="{{ now()->startOfYear()->format('Y-m-d') }}">
                            </div>
                            <div class="control-item">
                                <input type="date" id="sls-dealing-end-date" class="form-control modern-input" 
                                       value="{{ now()->endOfYear()->format('Y-m-d') }}">
                            </div>
                            <div class="control-item">
                                <button type="button" class="btn modern-apply-btn" id="sls-dealing-apply">
                                    Apply
                                </button>
                            </div>
                        </div>

                        <!-- Chart Container -->
                        <div class="chart-container" style="height: 350px; position: relative;">
                            <canvas id="sls-dealing-chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SLS Dealing Table (Right) -->
            <div class="col-lg-6 col-md-12 mb-4">
                <div class="card chart-card shadow-sm border-0">
                    <div class="card-body p-4">
                        <!-- Table Title -->
                        <div class="chart-title-section d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h5 class="chart-title mb-0">Dealing List</h5>
                            </div>
                        </div>
                        
                        <!-- Table Controls -->
                        <div class="chart-controls d-flex flex-wrap gap-2 mb-4">
                            @if(auth()->user()->role?->code === 'super_admin')
                            <div class="control-item">
                                <select id="sls-dealing-table-branch" class="form-select modern-select">
                                    <option value="">All Branch</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div class="control-item">
                                <input type="date" id="sls-dealing-table-start-date" class="form-control modern-input" 
                                       value="{{ now()->startOfYear()->format('Y-m-d') }}">
                            </div>
                            <div class="control-item">
                                <input type="date" id="sls-dealing-table-end-date" class="form-control modern-input" 
                                       value="{{ now()->endOfYear()->format('Y-m-d') }}">
                            </div>
                            <div class="control-item">
                                <button type="button" class="btn modern-apply-btn" id="sls-dealing-table-apply">
                                    Apply
                                </button>
                            </div>
                        </div>

                        {{-- Table with Sticky Footer for Total --}}
                        <div class="table-with-sticky-footer" style="height: 350px; overflow: auto; border: 1px solid #e3e6f0; border-radius: 0;">
                            <div style="min-width: 900px;">
                                <!-- Scrollable Table Body -->
                                <div class="table-body-scroll" style="overflow: visible;">
                                    <table class="table table-hover table-sm mb-0" id="sls-dealing-table">
                                        <thead style="background-color: #115641; position: sticky; top: 0; z-index: 10;">
                                            <tr>
                                                <th class="text-white fw-bold py-2 px-2" style="font-size: 11px; min-width: 120px;">Nama Sales</th>
                                                <th class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px; width: 100px;">Target Amount</th>
                                                <th class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px; width: 100px;">ACV Amount</th>
                                                <th class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px; width: 80px;">% (ACV/Target)</th>
                                                <th class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px; width: 80px;">Unit Sales</th>
                                                <th class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px; width: 100px;">Branch</th>
                                                <th class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px; min-width: 120px;">Periode</th>
                                            </tr>
                                        </thead>
                                        <tbody id="sls-dealing-tbody">
                                            <tr>
                                                <td colspan="7" class="text-center py-4">
                                                    <div class="text-success">
                                                        <div class="spinner-border text-success" role="status">
                                                            <span class="visually-hidden">Loading...</span>
                                                        </div>
                                                        <p class="mt-3 mb-0 text-muted">Loading SLS dealing data...</p>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot style="background-color: #115641; position: sticky; bottom: 0; z-index: 15;">
                                            <tr id="sls-dealing-total-row">
                                                <td class="text-white fw-bold py-2 px-2" style="font-size: 11px; border-radius: 0;">TOTAL</td>
                                                <td class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px;" id="total-target-amount">0</td>
                                                <td class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px;" id="total-acv-amount">0</td>
                                                <td class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px;" id="total-percentage">0%</td>
                                                <td class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px;" id="total-unit-sales">0</td>
                                                <td class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px;"></td>
                                                <td class="text-white fw-bold py-2 px-1 text-center" style="font-size: 10px;"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End side-by-side SLS Dealing row -->
    </div>

    <!-- SALES POTENTIAL DEALING BRANCH Section -->
    <div class="col-md-12 mb-4">
        <h2 class="font-weight-bold mb-4" style="font-size: 30px; color: #115641;">SALES POTENTIAL DEALING BRANCH</h2>
        
        <div class="card shadow" style="border-radius: 20px; overflow: hidden;">
            <div class="card-body p-0">
                {{-- Filter Controls --}}
                <div class="p-4 bg-light potential-dealing-section">
                    @if(auth()->user()->role?->code === 'super_admin')
                    {{-- Super Admin Layout - Single row with all filters --}}
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <label class="form-label">Branch</label>
                            <select id="potential-branch-branch" class="form-select source-control-input">
                                <option value="">All Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" id="potential-branch-start-date" class="form-control source-control-input" value="{{ now()->startOfYear()->format('Y-m-d') }}">
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <label class="form-label">End Date</label>
                            <input type="date" id="potential-branch-end-date" class="form-control source-control-input" value="{{ now()->endOfYear()->format('Y-m-d') }}">
                        </div>
                        <div class="col-lg-2 col-md-4 col-sm-6">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn source-apply-button w-100" id="potential-branch-apply">
                                <i class="fas fa-filter me-1"></i> Apply Filter
                            </button>
                        </div>
                    </div>
                    @else
                    {{-- Non-Admin Layout - Single row with all filters --}}
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-4 col-md-4 col-sm-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" id="potential-branch-start-date" class="form-control source-control-input" value="{{ now()->startOfYear()->format('Y-m-d') }}">
                        </div>
                        <div class="col-lg-4 col-md-4 col-sm-6">
                            <label class="form-label">End Date</label>
                            <input type="date" id="potential-branch-end-date" class="form-control source-control-input" value="{{ now()->endOfYear()->format('Y-m-d') }}">
                        </div>
                        <div class="col-lg-4 col-md-4 col-sm-6">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn source-apply-button w-100" id="potential-branch-apply">
                                <i class="fas fa-filter me-1"></i> Apply Filter
                            </button>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Responsive Table with Integrated Footer --}}
                <div class="source-conversion-responsive" id="potential-branch-container">
                    <!-- Scrollable Table Body -->
                    <div class="source-conversion-scroll">
                        <table class="table table-hover mb-0" id="potential-branch-table">
                            <thead style="background-color: #115641; position: sticky; top: 0; z-index: 20;">
                                <tr>
                                    <th class="text-white fw-bold py-3 px-4" style="border-radius: 0; position: sticky; top: 0; background-color: #115641;">Nama Sales</th>
                                    <th class="text-white fw-bold py-3 px-4 text-center" style="border-radius: 0; position: sticky; top: 0; background-color: #115641;">Warm + Hot Amount</th>
                                    <th class="text-white fw-bold py-3 px-4 text-center" style="border-radius: 0; position: sticky; top: 0; background-color: #115641;">Qty (W + H)</th>
                                    <th class="text-white fw-bold py-3 px-4 text-center" style="border-radius: 0; position: sticky; top: 0; background-color: #115641;">Avg Discount</th>
                                    <th class="text-white fw-bold py-3 px-4 text-center" style="border-radius: 0; position: sticky; top: 0; background-color: #115641;">Branch</th>
                                    <th class="text-white fw-bold py-3 px-4 text-center" style="background-color: #0d4534; position: sticky; top: 0; border-radius: 0;">Periode</th>
                                </tr>
                            </thead>
                            <tbody id="potential-branch-tbody">
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="text-success">
                                            <div class="spinner-border text-success" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <p class="mt-2 mb-0 text-muted">Loading potential dealing branch data...</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot style="background-color: #115641; position: sticky; bottom: 0; z-index: 15;">
                                <tr id="potential-branch-total-row">
                                    <td class="text-white fw-bold py-3 px-4" style="border-radius: 0;">TOTAL</td>
                                    <td class="text-white fw-bold py-3 px-4 text-center" id="total-branch-warm-hot-amount">0</td>
                                    <td class="text-white fw-bold py-3 px-4 text-center" id="total-branch-warm-hot-qty">0</td>
                                    <td class="text-white fw-bold py-3 px-4 text-center" id="total-branch-avg-discount">0%</td>
                                    <td class="text-white fw-bold py-3 px-4 text-center"></td>
                                    <td class="text-white fw-bold py-3 px-4 text-center" style="background-color: #0d4534;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SALES POTENTIAL DEALING LIST Section -->
    <div class="col-md-12 mb-4">
        <h2 class="font-weight-bold mb-4" style="font-size: 30px; color: #115641;">SALES POTENTIAL DEALING LIST</h2>
        
        <div class="card shadow" style="border-radius: 20px; overflow: hidden;">
            <div class="card-body p-0">
                {{-- Filter Controls --}}
                <div class="p-4 bg-light potential-dealing-section">
                    @if(auth()->user()->role?->code === 'super_admin')
                    {{-- Super Admin Layout - Single row with all filters --}}
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <label class="form-label">Branch</label>
                            <select id="potential-list-branch" class="form-select source-control-input">
                                <option value="">All Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" id="potential-list-start-date" class="form-control source-control-input" value="{{ now()->startOfYear()->format('Y-m-d') }}">
                        </div>
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <label class="form-label">End Date</label>
                            <input type="date" id="potential-list-end-date" class="form-control source-control-input" value="{{ now()->endOfYear()->format('Y-m-d') }}">
                        </div>
                        <div class="col-lg-3 col-md-4 col-sm-6">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn source-apply-button w-100" id="potential-list-apply">
                                <i class="fas fa-filter me-1"></i> Apply Filter
                            </button>
                        </div>
                    </div>
                    @else
                    {{-- Non-Admin Layout - Single row with all filters --}}
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-4 col-md-4 col-sm-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" id="potential-list-start-date" class="form-control source-control-input" value="{{ now()->startOfYear()->format('Y-m-d') }}">
                        </div>
                        <div class="col-lg-4 col-md-4 col-sm-6">
                            <label class="form-label">End Date</label>
                            <input type="date" id="potential-list-end-date" class="form-control source-control-input" value="{{ now()->endOfYear()->format('Y-m-d') }}">
                        </div>
                        <div class="col-lg-4 col-md-4 col-sm-6">
                            <label class="form-label">&nbsp;</label>
                            <button type="button" class="btn source-apply-button w-100" id="potential-list-apply">
                                <i class="fas fa-filter me-1"></i> Apply Filter
                            </button>
                        </div>
                    </div>
                    @endif
                </div>

                {{-- Responsive Table with Integrated Footer --}}
                <div class="source-conversion-responsive" id="potential-list-container">
                    <!-- Scrollable Table Body -->
                    <div class="source-conversion-scroll">
                        <table class="table table-hover mb-0" id="potential-list-table">
                            <thead style="background-color: #115641; position: sticky; top: 0; z-index: 20;">
                                <tr>
                                    <th class="text-white fw-bold py-3 px-4" style="border-radius: 0; position: sticky; top: 0; background-color: #115641;">Nama Customer</th>
                                    <th class="text-white fw-bold py-3 px-4 text-center" style="border-radius: 0; position: sticky; top: 0; background-color: #115641;">Status</th>
                                    <th class="text-white fw-bold py-3 px-4 text-center" style="border-radius: 0; position: sticky; top: 0; background-color: #115641;">Amount</th>
                                    <th class="text-white fw-bold py-3 px-4 text-center" style="border-radius: 0; position: sticky; top: 0; background-color: #115641;">Regional</th>
                                    <th class="text-white fw-bold py-3 px-4 text-center" style="border-radius: 0; position: sticky; top: 0; background-color: #115641;">Product</th>
                                    <th class="text-white fw-bold py-3 px-4 text-center" style="border-radius: 0; position: sticky; top: 0; background-color: #115641;">Last Activity</th>
                                    <th class="text-white fw-bold py-3 px-4 text-center" style="border-radius: 0; background-color: #0d4534; position: sticky; top: 0;">Data Validation</th>
                                </tr>
                            </thead>
                            <tbody id="potential-list-tbody">
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <div class="text-success">
                                            <div class="spinner-border text-success" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <p class="mt-2 mb-0 text-muted">Loading potential dealing list data...</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot style="background-color: #115641; position: sticky; bottom: 0; z-index: 15;">
                                <tr id="potential-list-total-row">
                                    <td class="text-white fw-bold py-3 px-4" style="border-radius: 0;">TOTAL</td>
                                    <td class="text-white fw-bold py-3 px-4 text-center" id="total-list-count">0</td>
                                    <td class="text-white fw-bold py-3 px-4 text-center" id="total-list-amount">0</td>
                                    <td class="text-white fw-bold py-3 px-4 text-center"></td>
                                    <td class="text-white fw-bold py-3 px-4 text-center"></td>
                                    <td class="text-white fw-bold py-3 px-4 text-center"></td>
                                    <td class="text-white fw-bold py-3 px-4 text-center" style="background-color: #0d4534;"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    

    

    {{-- <div class="row"> --}}
        {{-- <div class="col-md-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Group 1</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">Line chart with date and branch filters.</p>
                    <ul class="mb-0 ps-3">
                        <li>Target Global &ndash; Achievement Global (All)</li>
                        <li>Target Monthly &ndash; Achievement Monthly</li>
                        <li>Target Branch &ndash; Achievement Branch</li>
                        <li>Target Agent &ndash; Achievement Agent</li>
                        <li>Target Government Project &ndash; Achievement Government Project</li>
                    </ul>
                </div>
            </div>
        </div> --}}

        {{-- <div class="col-md-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Group 2</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2">Line chart with date and branch filters.</p>
                    <ul class="mb-0 ps-3">
                        <li>Target Branch &ndash; Achievement Branch</li>
                        <li>Target Agent &ndash; Achievement Agent</li>
                        <li>Target Government Project &ndash; Achievement Government Project</li>
                    </ul>
                </div>
            </div>
        </div> --}}

        <div class="col-md-12 mb-4 d-none">
  <div class="card shadow">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Trend Total Penjualan per Branch</h6>
      <button class="btn btn-link collapse-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#branchSalesBody" aria-expanded="true">
        <i class="fas fa-angle-down"></i>
      </button>
    </div>
    <div id="branchSalesBody" class="collapse show">
      <div class="card-body">
        <div class="row g-2 mb-3">
          <div class="col-md-4">
            {{-- pilih hingga 3 branch --}}
            <select id="branch_sales_branches" class="form-select form-select-sm select2" multiple>
              @foreach ($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
              @endforeach
            </select>
            <small class="text-muted">Pilih maks. 3 branch, kosongkan untuk Top 3 otomatis</small>
          </div>
          <div class="col-md-3">
            <input type="date" id="branch_sales_start" class="form-control form-control-sm"
                   value="{{ $defaultYtdStart }}" onfocus="this.showPicker()">
          </div>
          <div class="col-md-3">
            <input type="date" id="branch_sales_end" class="form-control form-control-sm"
                   value="{{ $defaultYtdEnd }}" onfocus="this.showPicker()">
          </div>
          <div class="col-md-2 d-grid">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="branch_sales_apply">
              <i class="bi bi-search me-1"></i> Apply
            </button>
          </div>
        </div>

        <div style="height: 360px;">
          <canvas id="branch_sales_chart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- <div class="col-md-12 mb-4">
  <div class="card shadow">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Cold Leads per Branch (Count & Nominal)</h6>
      <button class="btn btn-link collapse-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#coldLeadsBranchBody" aria-expanded="true">
        <i class="fas fa-angle-down"></i>
      </button>
    </div>
    <div id="coldLeadsBranchBody" class="collapse show">
      <div class="card-body">
        <div class="row g-2 mb-3">
          <div class="col-md-4">
            <select id="cl_branch_ids" class="form-select form-select-sm select2" multiple>
              @foreach ($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
              @endforeach
            </select>
            <small class="text-muted">Pilih maks. 3 branch, kosongkan untuk Top 3 otomatis</small>
          </div>
          <div class="col-md-3">
            <input type="date" id="cl_start" class="form-control form-control-sm" value="{{ $defaultYtdStart }}" onfocus="this.showPicker()">
          </div>
          <div class="col-md-3">
            <input type="date" id="cl_end" class="form-control form-control-sm" value="{{ $defaultYtdEnd }}" onfocus="this.showPicker()">
          </div>
          <div class="col-md-2 d-grid">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="cl_apply">
              <i class="bi bi-search me-1"></i> Apply
            </button>
          </div>
        </div>

        <div class="mb-4" style="height: 320px;">
          <canvas id="cl_count_chart"></canvas>
        </div>
        <div style="height: 320px;">
          <canvas id="cl_amount_chart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div> --}}

<!-- WARM Leads per Branch -->
{{-- <div class="col-md-12 mb-4">
  <div class="card shadow">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Warm Leads per Branch (Count & Nominal)</h6>
      <button class="btn btn-link collapse-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#warmLeadsBranchBody" aria-expanded="true">
        <i class="fas fa-angle-down"></i>
      </button>
    </div>
    <div id="warmLeadsBranchBody" class="collapse show">
      <div class="card-body">
        <div class="row g-2 mb-3">
          <div class="col-md-4">
            <select id="wl_branch_ids" class="form-select form-select-sm select2" multiple>
              @foreach ($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
              @endforeach
            </select>
            <small class="text-muted">Pilih maks. 3 branch, kosongkan untuk Top 3 otomatis</small>
          </div>
          <div class="col-md-3">
            <input type="date" id="wl_start" class="form-control form-control-sm" value="{{ $defaultYtdStart }}" onfocus="this.showPicker()">
          </div>
          <div class="col-md-3">
            <input type="date" id="wl_end" class="form-control form-control-sm" value="{{ $defaultYtdEnd }}" onfocus="this.showPicker()">
          </div>
          <div class="col-md-2 d-grid">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="wl_apply">
              <i class="bi bi-search me-1"></i> Apply
            </button>
          </div>
        </div>

        <div class="mb-4" style="height: 320px;">
          <canvas id="wl_count_chart"></canvas>
        </div>
        <div style="height: 320px;">
          <canvas id="wl_amount_chart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div> --}}

<!-- HOT Leads per Branch -->
{{-- <div class="col-md-12 mb-4">
  <div class="card shadow">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Hot Leads per Branch (Count & Nominal)</h6>
      <button class="btn btn-link collapse-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#hotLeadsBranchBody" aria-expanded="true">
        <i class="fas fa-angle-down"></i>
      </button>
    </div>
    <div id="hotLeadsBranchBody" class="collapse show">
      <div class="card-body">
        <div class="row g-2 mb-3">
          <div class="col-md-4">
            <select id="hl_branch_ids" class="form-select form-select-sm select2" multiple>
              @foreach ($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
              @endforeach
            </select>
            <small class="text-muted">Pilih maks. 3 branch, kosongkan untuk Top 3 otomatis</small>
          </div>
          <div class="col-md-3">
            <input type="date" id="hl_start" class="form-control form-control-sm" value="{{ $defaultYtdStart }}" onfocus="this.showPicker()">
          </div>
          <div class="col-md-3">
            <input type="date" id="hl_end" class="form-control form-control-sm" value="{{ $defaultYtdEnd }}" onfocus="this.showPicker()">
          </div>
          <div class="col-md-2 d-grid">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="hl_apply">
              <i class="bi bi-search me-1"></i> Apply
            </button>
          </div>
        </div>

        <div class="mb-4" style="height: 320px;">
          <canvas id="hl_count_chart"></canvas>
        </div>
        <div style="height: 320px;">
          <canvas id="hl_amount_chart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div> --}}

<div class="col-md-12 mb-4">
  <div class="card shadow">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Sales Performance</h6>
      <button class="btn btn-link collapse-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#salesPerfBody" aria-expanded="true">
        <i class="fas fa-angle-down"></i>
      </button>
    </div>
    <div id="salesPerfBody" class="collapse show">
      <div class="card-body">
        <div class="row g-2 mb-3">
          <div class="col-md-3">
            <select id="sp_branch" class="form-select form-select-sm select2">
              <option value="">All Branch</option>
              @foreach ($branches as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <input type="date" id="sp_start" class="form-control form-control-sm" value="{{ $defaultYtdStart }}" onfocus="this.showPicker()">
          </div>
          <div class="col-md-3">
            <input type="date" id="sp_end" class="form-control form-control-sm" value="{{ $defaultYtdEnd }}" onfocus="this.showPicker()">
          </div>
          <div class="col-md-3 d-grid">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="sp_apply">
              <i class="bi bi-search me-1"></i> Apply
            </button>
          </div>
        </div>

        <div style="height: 360px;" class="mb-4">
          <canvas id="sp_bar"></canvas>
        </div>

        <div class="row g-2 mb-3">
          <div class="col-md-9">
            <select id="sa_sales_ids" class="form-select form-select-sm select2" multiple>
              @foreach ($salesUsers as $s)
                <option value="{{ $s->id }}">{{ $s->name }}</option>
              @endforeach
            </select>
            <small class="text-muted">Pilih maks. 3 sales untuk tren Achievement% (kosongkan = Top 3 otomatis)</small>
          </div>
          <div class="col-md-3 d-grid">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="sa_apply">
              <i class="bi bi-graph-up-arrow me-1"></i> Refresh Trend
            </button>
          </div>
        </div>

        <div style="height: 360px;">
          <canvas id="sa_line"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

        <div class="col-md-12 mb-4">
  <div class="card shadow">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
      <h6 class="m-0 font-weight-bold text-primary">Trend Orders Bulanan (YTD)</h6>
      <button class="btn btn-link collapse-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#ordersMonthlyBody" aria-expanded="true">
        <i class="fas fa-angle-down"></i>
      </button>
    </div>
    <div id="ordersMonthlyBody" class="collapse show">
      <div class="card-body">
        <div class="row g-2 mb-3">
          <div class="col-md-3">
            <select id="orders_branch" class="form-select form-select-sm select2">
              <option value="">All Branch</option>
              @foreach ($branches as $branch)
                <option value="{{ $branch->id }}" {{ $currentBranchId == $branch->id ? 'selected' : '' }}>
                  {{ $branch->name }}
                </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <input type="date" id="orders_start" class="form-control form-control-sm"
                   value="{{ $defaultYtdStart }}" onfocus="this.showPicker()">
          </div>
          <div class="col-md-3">
            <input type="date" id="orders_end" class="form-control form-control-sm"
                   value="{{ $defaultYtdEnd }}" onfocus="this.showPicker()">
          </div>
          <div class="col-md-3 d-grid">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="orders_apply">
              <i class="bi bi-search me-1"></i> Apply Filters
            </button>
          </div>
        </div>

        <div style="height: 360px;">
          <canvas id="orders_monthly_chart"></canvas>
        </div>
      </div>
    </div>
  </div>
</div>

        {{-- <div class="col-md-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Lead Overview</h6>
                    <button class="btn btn-link collapse-toggle" type="button" data-bs-toggle="collapse"
                        data-bs-target="#group3Body" aria-expanded="true">
                        <i class="fas fa-angle-down"></i>
                    </button>
                </div>
                <div id="group3Body" class="collapse show">
                    <div class="card-body">
                        <div class="row g-2 mb-3">
                            <div class="col-md-3">
                                <select id="overview_branch" class="form-select form-select-sm select2">
                                    <option value="">All Branch</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" {{ $currentBranchId == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="date" id="overview_start" class="form-control form-control-sm"
                                    value="{{ $defaultStart }}" onfocus="this.showPicker()">
                            </div>
                            <div class="col-md-3">
                                <input type="date" id="overview_end" class="form-control form-control-sm"
                                    value="{{ $defaultEnd }}" onfocus="this.showPicker()">
                            </div>
                            <div class="col-md-3 d-grid">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="overview_apply">
                                    <i class="bi bi-search me-1"></i> Apply Filters
                                </button>
                            </div>
                        </div>
                        <div style="height: 300px;">
                            <canvas id="overview_chart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div> --}}

        {{-- <div class="col-md-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Konversi Leads</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Konversi Cold to Warm</h6>
                                </div>
                                <div class="card-body">
                                    <div style="height: 300px;">
                                        <canvas id="cw_chart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Konversi Warm to Hot</h6>
                                </div>
                                <div class="card-body">
                                    <div style="height: 300px;">
                                        <canvas id="wh_chart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> --}}

        {{-- <div class="col-md-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Jumlah Leads Total</h6>
                    <button class="btn btn-link collapse-toggle" type="button" data-bs-toggle="collapse"
                        data-bs-target="#group5Body" aria-expanded="true">
                        <i class="fas fa-angle-down"></i>
                    </button>
                </div>
                <div id="group5Body" class="collapse show">
                    <div class="card-body">
                        <div class="row g-2 mb-3">
                            <div class="col-md-3">
                                <select id="lead_total_branch" class="form-select form-select-sm select2">
                                    <option value="">All Branch</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}"
                                            {{ $currentBranchId == $branch->id ? 'selected' : '' }}>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="date" id="lead_total_start" class="form-control form-control-sm"
                                    value="{{ $defaultStart }}" onfocus="this.showPicker()">
                            </div>
                            <div class="col-md-3">
                                <input type="date" id="lead_total_end" class="form-control form-control-sm"
                                    value="{{ $defaultEnd }}" onfocus="this.showPicker()">
                            </div>
                            <div class="col-md-3 d-grid">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="lead_total_apply">
                                    <i class="bi bi-search me-1"></i> Apply Filters
                                </button>
                            </div>
                        </div>
                        <div style="height: 300px;">
                            <canvas id="lead_total_chart"></canvas>
                        </div>
                    </div> <!-- end card-body -->
                </div>
            </div>
        </div> --}}

        {{-- <div class="col-md-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Jumlah Quotation</h6>
                    <button class="btn btn-link collapse-toggle" type="button" data-bs-toggle="collapse"
                        data-bs-target="#group6Body" aria-expanded="true">
                        <i class="fas fa-angle-down"></i>
                    </button>
                </div>
                <div id="group6Body" class="collapse show">
                    <div class="card-body">
                        <div class="row g-2 mb-3">
                            <div class="col-md-3">
                                <select id="quotation_branch" class="form-select form-select-sm select2">
                                    <option value="">All Branch</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}"
                                            {{ $currentBranchId == $branch->id ? 'selected' : '' }}>{{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="date" id="quotation_start" class="form-control form-control-sm"
                                    value="{{ $defaultStart }}" onfocus="this.showPicker()">
                            </div>
                            <div class="col-md-3">
                                <input type="date" id="quotation_end" class="form-control form-control-sm"
                                    value="{{ $defaultEnd }}" onfocus="this.showPicker()">
                            </div>
                            <div class="col-md-3 d-grid">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="quotation_apply">
                                    <i class="bi bi-search me-1"></i> Apply Filters
                                </button>
                            </div>
                        </div>
                        <div style="height: 300px;">
                            <canvas id="quotation_chart"></canvas>
                        </div>
                    </div> <!-- end card-body -->
                </div>
            </div>
        </div> --}}

        {{-- <div class="col-md-12 mb-4">
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Jumlah Leads Masuk Berdasarkan Source</h6>
                    <button class="btn btn-link collapse-toggle" type="button" data-bs-toggle="collapse"
                        data-bs-target="#group7Body" aria-expanded="true">
                        <i class="fas fa-angle-down"></i>
                    </button>
                </div>
                <div id="group7Body" class="collapse show">
                    <div class="card-body">
                        <div class="mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Jumlah Leads Masuk Berdasarkan Source -
                                        Cold</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-3">
                                            <select id="cold_branch" class="form-select form-select-sm select2">
                                                <option value="">All Branch</option>
                                                @foreach ($branches as $branch)
                                                    <option value="{{ $branch->id }}"
                                                        {{ $currentBranchId == $branch->id ? 'selected' : '' }}>
                                                        {{ $branch->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="date" id="cold_start" class="form-control form-control-sm"
                                                value="{{ $defaultStart }}" onfocus="this.showPicker()">
                                        </div>
                                        <div class="col-md-3">
                                            <input type="date" id="cold_end" class="form-control form-control-sm"
                                                value="{{ $defaultEnd }}" onfocus="this.showPicker()">
                                        </div>
                                        <div class="col-md-3 d-grid">
                                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                                id="cold_apply">
                                                <i class="bi bi-search me-1"></i> Apply Filters
                                            </button>
                                        </div>
                                    </div>
                                    <div style="height: 300px;">
                                        <canvas id="cold_chart"></canvas>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Jumlah Leads Masuk Berdasarkan Source -
                                        Warm</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-3">
                                            <select id="warm_branch" class="form-select form-select-sm select2">
                                                <option value="">All Branch</option>
                                                @foreach ($branches as $branch)
                                                    <option value="{{ $branch->id }}"
                                                        {{ $currentBranchId == $branch->id ? 'selected' : '' }}>
                                                        {{ $branch->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="date" id="warm_start" class="form-control form-control-sm"
                                                value="{{ $defaultStart }}" onfocus="this.showPicker()">
                                        </div>
                                        <div class="col-md-3">
                                            <input type="date" id="warm_end" class="form-control form-control-sm"
                                                value="{{ $defaultEnd }}" onfocus="this.showPicker()">
                                        </div>
                                        <div class="col-md-3 d-grid">
                                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                                id="warm_apply">
                                                <i class="bi bi-search me-1"></i> Apply Filters
                                            </button>
                                        </div>
                                    </div>
                                    <div style="height: 300px;">
                                        <canvas id="warm_chart"></canvas>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="card shadow">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Jumlah Leads Masuk Berdasarkan Source -
                                        Hot</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-2 mb-3">
                                        <div class="col-md-3">
                                            <select id="hot_branch" class="form-select form-select-sm select2">
                                                <option value="">All Branch</option>
                                                @foreach ($branches as $branch)
                                                    <option value="{{ $branch->id }}"
                                                        {{ $currentBranchId == $branch->id ? 'selected' : '' }}>
                                                        {{ $branch->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <input type="date" id="hot_start" class="form-control form-control-sm"
                                                value="{{ $defaultStart }}" onfocus="this.showPicker()">
                                        </div>
                                        <div class="col-md-3">
                                            <input type="date" id="hot_end" class="form-control form-control-sm"
                                                value="{{ $defaultEnd }}" onfocus="this.showPicker()">
                                        </div>
                                        <div class="col-md-3 d-grid">
                                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                                id="hot_apply">
                                                <i class="bi bi-search me-1"></i> Apply Filters
                                            </button>
                                        </div>
                                    </div>
                                    <div style="height: 300px;">
                                        <canvas id="hot_chart"></canvas>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div> <!-- end card-body -->
                </div>
            </div>
        </div> --}}

    

    @endsection

    @section('scripts')
        @parent
        <script src="{{ asset('sb-admin-2/vendor/chart.js/Chart.min.js') }}"></script>
        <script>
          let svtPercentChart;

function loadAchievementMonthlyPercent() {
  const params = { year: $('#svt_year').val() };
  $.post('{{ route('dashboard.sales-achievement-monthly-percent') }}', params, function(res){
    const labels = res.labels || [];
    const datasets = (res.datasets || []).map((d,i)=>({
      label: d.label,
      data:  d.data,
      backgroundColor: d.color || '#115641',
      borderColor: d.color || '#115641',
      borderWidth: 0,
      borderRadius: 6,
      borderSkipped: false
    }));

    const ctx = document.getElementById('svt_percent_chart').getContext('2d');
    if (svtPercentChart) svtPercentChart.destroy();
    svtPercentChart = new Chart(ctx, {
      type: 'bar',
      data: { labels, datasets },
      options: {
        maintainAspectRatio: false,
        responsive: true,
        plugins: {
          legend: {
            display: true,
            position: 'bottom',
            labels: {
              usePointStyle: true,
              padding: 20,
              font: {
                size: 12,
                weight: '500'
              },
              color: '#374151'
            }
          }
        },
        tooltips: {
          mode: 'index', 
          intersect: false,
          backgroundColor: 'rgba(17, 86, 65, 0.9)',
          titleColor: '#fff',
          bodyColor: '#fff',
          borderColor: '#115641',
          borderWidth: 1,
          cornerRadius: 8,
          callbacks: {
            label: (t, data) => {
              const ds = data.datasets[t.datasetIndex];
              const v  = typeof t.yLabel === 'string' ? parseFloat(t.yLabel) : t.yLabel;
              return ds.label + ': ' + (v ? v.toFixed(2) : '0.00') + '%';
            }
          }
        },
        scales: {
          x: {
            grid: {
              display: false
            },
            ticks: {
              color: '#6B7280',
              font: {
                size: 11,
                weight: '500'
              }
            }
          },
          y: {
            beginAtZero: true,
            grid: {
              color: '#F3F4F6',
              drawBorder: false
            },
            ticks: {
              color: '#6B7280',
              font: {
                size: 11,
                weight: '500'
              },
              callback: v => (v ? v.toFixed(0) : 0) + '%'
            }
          }
        },
        elements: {
          bar: {
            borderRadius: 6,
            borderSkipped: false
          }
        }
      }
    });
  });
}

      let tvsmChart;
function loadTargetVsSalesMonthly() {
  const params = {
    scope: $('#tvsm_scope').val(),
    year:  $('#tvsm_year').val()
  };

  $.post('{{ route('dashboard.target-vs-sales-monthly') }}', params, function(res){
    console.log('Target vs Sales Monthly Response:', res);
    const labels = res.labels || [];
    const series = res.series || [];
    console.log('Series data:', series);
    const ctx = document.getElementById('tvsm_chart').getContext('2d');

    if (tvsmChart) tvsmChart.destroy();

    // siapkan datasets dinamis (2 atau 3 seri)
    const datasets = [];

    if (series[0]) {
      datasets.push({
        label: series[0].label || 'Target',
        data: series[0].data || [],
        borderColor: '#F97316',
        backgroundColor: 'rgba(249, 115, 22, 0.1)',
        fill: false,
        tension: 0.4,
        pointRadius: 5,
        pointHoverRadius: 7,
        pointBackgroundColor: '#F97316',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        borderWidth: 3
      });
    }

    if (series[1]) {
      datasets.push({
        label: series[1].label || 'Sales',
        data: series[1].data || [],
        borderColor: '#115641',
        backgroundColor: 'rgba(17, 86, 65, 0.1)',
        fill: false,
        tension: 0.4,
        pointRadius: 5,
        pointHoverRadius: 7,
        pointBackgroundColor: '#115641',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        borderWidth: 3
      });
    }

    // ⬇️ seri baru: All Branch Target (jika ada)
    if (series[2] && series[2].data && series[2].data.length > 0) {
      console.log('Adding All Branch Target series:', series[2]);
      datasets.push({
        label: series[2].label || 'All Branch Target',
        data: series[2].data || [],
        borderColor: '#6B7280',
        backgroundColor: 'rgba(107, 114, 128, 0.1)',
        fill: false,
        tension: 0.4,
        pointRadius: 4,
        pointHoverRadius: 6,
        borderWidth: 2,
        borderDash: [8, 4],
        pointBackgroundColor: '#6B7280',
        pointBorderColor: '#fff',
        pointBorderWidth: 1
      });
    } else {
      console.log('All Branch Target series not found or empty:', series[2]);
    }

    tvsmChart = new Chart(ctx, {
      type: 'line',
      data: { labels, datasets },
      options: {
        maintainAspectRatio: false,
        responsive: true,
        plugins: {
          legend: {
            display: true,
            position: 'bottom',
            labels: {
              usePointStyle: true,
              padding: 20,
              font: {
                size: 12,
                weight: '500'
              },
              color: '#374151'
            }
          }
        },
        tooltips: {
          mode: 'index', 
          intersect: false,
          backgroundColor: 'rgba(17, 86, 65, 0.9)',
          titleColor: '#fff',
          bodyColor: '#fff',
          borderColor: '#115641',
          borderWidth: 1,
          cornerRadius: 8,
          callbacks: {
            label: function(tooltipItem, data) {
              const ds = data.datasets[tooltipItem.datasetIndex];
              const val = tooltipItem.yLabel || 0;
              return ds.label + ': Rp' + number_format(val, 0, ',', '.');
            }
          }
        },
        scales: {
          x: {
            grid: {
              display: false
            },
            ticks: {
              color: '#6B7280',
              font: {
                size: 11,
                weight: '500'
              }
            }
          },
          y: {
            beginAtZero: true,
            grid: {
              color: '#F3F4F6',
              drawBorder: false
            },
            ticks: {
              color: '#6B7280',
              font: {
                size: 11,
                weight: '500'
              },
              callback: function(value){ return 'Rp' + number_format(value, 0, ',', '.'); }
            }
          }
        },
        elements: {
          point: {
            hoverBorderWidth: 3
          }
        }
      }
    });
  });
}

            let donutGlobalChart, donutAllChart, donutGlobalBranchChart, donutAllBranchChart, donutBranchSingleChart;
const donutBranchCharts = {};

function renderDonut(ctx, achieved, target) {
  // clamp agar tidak negatif/lebih dari target pada visual
  const inTarget = Math.min(achieved, target);
  const remaining = Math.max(target - inTarget, 0);

  return new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Achieved', 'Remaining'],
      datasets: [{
        data: [inTarget, remaining],
        backgroundColor: ['#115641', '#e9ecef'],
        borderWidth: 0,
        borderRadius: 8
      }]
    },
    options: {
      maintainAspectRatio: false,
      cutoutPercentage: 70,
      legend: { display: false },
      tooltips: {
        enabled: true,
        backgroundColor: '#115641',
        titleFontColor: '#fff',
        bodyFontColor: '#fff',
        borderColor: '#115641',
        borderWidth: 1,
        callbacks: {
          label: function(t, d) {
            const label = d.labels[t.index] || '';
            const val = d.datasets[0].data[t.index] || 0;
            const percent = target > 0 ? ((val / target) * 100).toFixed(1) : '0.0';
            return label + ': ' + percent + '% (Rp' + number_format(val, 0, ',', '.') + ')';
          }
        }
      },
      animation: {
        animateRotate: true,
        duration: 1000,
        easing: 'easeOutQuart'
      }
    }
  });
}

function loadSalesAchievementDonuts() {
  const params = {
    start_date: $('#donut_start').val(),
    end_date:   $('#donut_end').val()
  };

  $.post('{{ route('dashboard.sales-achievement-donut') }}', params, function(res){
    // Check if user is super admin or branch user
    const isSuperAdmin = {{ auth()->user()->role?->code === 'super_admin' ? 'true' : 'false' }};
    
    if (isSuperAdmin) {
        // SUPER ADMIN LAYOUT
        
        // Global Achievement
        const g = res.global || { achieved:0, target:10000000, percent:0 };
        const gctx = document.getElementById('donut_global');
        if (gctx) {
            if (donutGlobalChart) donutGlobalChart.destroy();
            donutGlobalChart = renderDonut(gctx.getContext('2d'), g.achieved, g.target);
            
            // Update stats
            $('#global_achievement_pct').text((g.percent ? g.percent.toFixed(0) : '0') + '%');
            $('#global_achievement_amount').text('Rp' + number_format(g.achieved,0,',','.'));
            $('#global_target_amount').text('Rp' + number_format(g.target,0,',','.'));
        }

        // All Branch Target
        const all = res.all_branch || { achieved:0, target:10000000, percent:0 };
        const allCtx = document.getElementById('donut_all');
        if (allCtx) {
            if (donutAllChart) donutAllChart.destroy();
            donutAllChart = renderDonut(allCtx.getContext('2d'), all.achieved, all.target);
            
            // Update stats
            $('#all_achievement_pct').text((all.percent ? all.percent.toFixed(0) : '0') + '%');
            $('#all_achievement_amount').text('Rp' + number_format(all.achieved,0,',','.'));
            $('#all_target_amount').text('Rp' + number_format(all.target,0,',','.'));
        }

        // Branch Achievements List
        const container = $('#branch_achievements_container');
        container.empty();
        
        const branches = res.branches || [];
        if (!branches.length) {
            container.append('<div class="text-center text-muted py-4">Tidak ada data branch</div>');
            return;
        }

        branches.forEach((b, idx) => {
            const percentage = b.percent ? b.percent.toFixed(0) : '0';
            const branchItem = $(`
                <div class="branch-item">
                    <div class="branch-info">
                        <span class="branch-name">BRANCH ${b.label.toUpperCase()}</span>
                        <div class="branch-stats">
                            <span class="branch-target">Target: Rp. ${number_format(b.target,0,',','.')}</span>
                            <span class="branch-achievement">Achievement: ${percentage}% - Rp${number_format(b.achieved,0,',','.')}</span>
                        </div>
                        <div class="branch-progress">
                            <div class="progress-bar-container">
                                <div class="progress-bar-fill" style="width: ${percentage}%;"></div>
                            </div>
                            <div class="progress-percentage">${percentage}%</div>
                        </div>
                    </div>
                </div>
            `);
            container.append(branchItem);
        });
        
    } else {
        // BRANCH USER LAYOUT
        
        // Global Achievement for Branch User
        const g = res.global || { achieved:0, target:10000000, percent:0 };
        const gBranchCtx = document.getElementById('donut_global_branch');
        if (gBranchCtx) {
            if (donutGlobalBranchChart) donutGlobalBranchChart.destroy();
            donutGlobalBranchChart = renderDonut(gBranchCtx.getContext('2d'), g.achieved, g.target);
            
            // Update stats
            $('#global_branch_achievement_pct').text((g.percent ? g.percent.toFixed(0) : '0') + '%');
            $('#global_branch_achievement_amount').text('Rp' + number_format(g.achieved,0,',','.'));
            $('#global_branch_target_amount').text('Rp' + number_format(g.target,0,',','.'));
        }

        // All Branch Target (Plan) for Branch User
        const all = res.all_branch || { achieved:0, target:10000000, percent:0 };
        const allBranchCtx = document.getElementById('donut_all_branch');
        if (allBranchCtx) {
            if (donutAllBranchChart) donutAllBranchChart.destroy();
            donutAllBranchChart = renderDonut(allBranchCtx.getContext('2d'), all.achieved, all.target);
            
            // Update stats
            $('#all_branch_achievement_pct').text((all.percent ? all.percent.toFixed(0) : '0') + '%');
            $('#all_branch_achievement_amount').text('Rp' + number_format(all.achieved,0,',','.'));
            $('#all_branch_target_amount').text('Rp' + number_format(all.target,0,',','.'));
        }

        // Single Branch Achievement for Branch User
        const branches = res.branches || [];
        const currentBranch = branches.length > 0 ? branches[0] : { achieved:0, target:10000000, percent:0 };
        const branchSingleCtx = document.getElementById('donut_branch_single');
        if (branchSingleCtx) {
            if (donutBranchSingleChart) donutBranchSingleChart.destroy();
            donutBranchSingleChart = renderDonut(branchSingleCtx.getContext('2d'), currentBranch.achieved, currentBranch.target);
            
            // Update stats
            $('#branch_single_achievement_pct').text((currentBranch.percent ? currentBranch.percent.toFixed(0) : '0') + '%');
            $('#branch_single_achievement_amount').text('Rp' + number_format(currentBranch.achieved,0,',','.'));
            $('#branch_single_target_amount').text('Rp' + number_format(currentBranch.target,0,',','.'));
        }
    }
  });
}


let spBarChart, saLineChart;

// Bar: Cold/Warm/Hot/Deal per Sales
function loadSalesPerformanceBar() {
  const params = {
    branch_id:  $('#sp_branch').val(),
    start_date: $('#sp_start').val(),
    end_date:   $('#sp_end').val()
  };
  $.post('{{ route('dashboard.sales-performance-bar') }}', params, function(res){
    const labels = res.labels || [];
    const ds = res.datasets || [];
    const ctx = document.getElementById('sp_bar').getContext('2d');
    if (spBarChart) spBarChart.destroy();
    spBarChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: ds.map(d => ({
          label: d.label,
          data: d.data,
          backgroundColor: d.color
        }))
      },
      options: {
        maintainAspectRatio: false,
        legend: { position: 'bottom' },
        tooltips: {
          mode: 'index', intersect: false,
          callbacks: { label: (t, data) => {
            const ds = data.datasets[t.datasetIndex];
            return ds.label + ': ' + number_format(t.yLabel,0,',','.');
          }}
        },
        scales: {
          xAxes: [{ stacked: false, gridLines: { display:false } }],
          yAxes: [{ stacked: false, ticks: { beginAtZero:true, callback: v => number_format(v,0,',','.') } }]
        }
      }
    });
  });
}

// Line: Achievement % per Sales (Top 3 atau pilihan)
function loadSalesAchievementTrend() {
  const params = {
    sales_ids: ($('#sa_sales_ids').val() || []).slice(0,3),
    branch_id: $('#sp_branch').val(), // sinkron dengan filter branch di atas
    start_date: $('#sp_start').val(),
    end_date:   $('#sp_end').val()
  };
  $.post('{{ route('dashboard.sales-achievement-trend') }}', params, function(res){
    const labels = res.labels || [];
    const series = res.series || [];
    const colors = ['#4e73df', '#e74a3b', '#1cc88a', '#f6c23e']; // sampai 4 warna

    const ctx = document.getElementById('sa_line').getContext('2d');
    if (saLineChart) saLineChart.destroy();
    saLineChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: series.map((s,i)=>({
          label: s.label,
          data: s.data,
          borderColor: colors[i % colors.length],
          backgroundColor: 'rgba(0,0,0,0)',
          fill: false,
          lineTension: 0,
          pointRadius: 3
        }))
      },
      options: {
        maintainAspectRatio: false,
        legend: { position: 'bottom' },
        tooltips: {
          mode: 'index', intersect: false,
          callbacks: {
            label: function(t, data) {
              const ds = data.datasets[t.datasetIndex];
              const val = typeof t.yLabel === 'string' ? parseFloat(t.yLabel) : t.yLabel;
              return ds.label + ': ' + (val ? val.toFixed(2) : '0.00') + '%';
            }
          }
        },
        scales: {
          xAxes: [{ gridLines: { display:false } }],
          yAxes: [{
            ticks: {
              beginAtZero:true,
              callback: v => (v ? v.toFixed(0) : 0) + '%'
            }
          }]
        }
      }
    });
  });
}


        const lbCharts = {}; // simpan instance chart per prefix

// Function commented out because HTML elements are commented
/*
function loadLeadsBranchTrend(prefix, status) {
  const params = {
    status: status, // 'cold' | 'warm' | 'hot'
    branch_ids: $('#' + prefix + '_branch_ids').val() || [],
    start_date: $('#' + prefix + '_start').val(),
    end_date:   $('#' + prefix + '_end').val()
  };

  $.post('{{ route('dashboard.leads-branch-trend') }}', params, function(res) {
    const labels       = res.labels || [];
    const seriesCount  = res.series_count  || [];
    const seriesAmount = res.series_amount || [];
    const targetCount  = res.target_count  || [];
    const targetAmount = res.target_amount || [];

    const colors = ['#4e73df', '#e74a3b', '#1cc88a']; // branch lines

    // COUNT chart
    const ctx1 = document.getElementById(prefix + '_count_chart').getContext('2d');
    if (lbCharts[prefix + '_count']) lbCharts[prefix + '_count'].destroy();
    const dsCount = seriesCount.map((s, i) => ({
      label: s.label + ' - Leads',
      data: s.data,
      borderColor: colors[i % colors.length],
      backgroundColor: 'rgba(0,0,0,0)',
      fill: false,
      lineTension: 0,
      pointRadius: 3
    }));
    // tambahan 1 line: TARGET
    if (targetCount.length === labels.length) {
      dsCount.push({
        label: 'Target',
        data: targetCount,
        borderColor: '#f6c23e',
        backgroundColor: 'rgba(0,0,0,0)',
        fill: false,
        lineTension: 0,
        pointRadius: 0,
        borderWidth: 2,
        borderDash: [6,4]
      });
    }
    lbCharts[prefix + '_count'] = new Chart(ctx1, {
      type: 'line',
      data: { labels, datasets: dsCount },
      options: {
        maintainAspectRatio: false,
        legend: { position: 'bottom' },
        tooltips: {
          mode: 'index', intersect: false,
          callbacks: {
            label: function(t, d) {
              const ds  = d.datasets[t.datasetIndex];
              const val = t.yLabel;
              return ds.label + ': ' + number_format(val, 0, ',', '.');
            }
          }
        },
        scales: {
          xAxes: [{ gridLines: { display: false } }],
          yAxes: [{ ticks: { beginAtZero: true, callback: v => number_format(v,0,',','.') } }]
        }
      }
    });

    // AMOUNT chart (Rupiah)
    const ctx2 = document.getElementById(prefix + '_amount_chart').getContext('2d');
    if (lbCharts[prefix + '_amount']) lbCharts[prefix + '_amount'].destroy();
    const dsAmt = seriesAmount.map((s, i) => ({
      label: s.label + ' - Nominal',
      data: s.data,
      borderColor: colors[i % colors.length],
      backgroundColor: 'rgba(0,0,0,0)',
      fill: false,
      lineTension: 0,
      pointRadius: 3
    }));
    // tambahan 1 line: TARGET (nominal)
    if (targetAmount.length === labels.length) {
      dsAmt.push({
        label: 'Target',
        data: targetAmount,
        borderColor: '#6c757d',
        backgroundColor: 'rgba(0,0,0,0)',
        fill: false,
        lineTension: 0,
        pointRadius: 0,
        borderWidth: 2,
        borderDash: [6,4]
      });
    }
    lbCharts[prefix + '_amount'] = new Chart(ctx2, {
      type: 'line',
      data: { labels, datasets: dsAmt },
      options: {
        maintainAspectRatio: false,
        legend: { position: 'bottom' },
        tooltips: {
          mode: 'index', intersect: false,
          callbacks: {
            label: function(t, d) {
              const ds  = d.datasets[t.datasetIndex];
              const val = t.yLabel;
              return ds.label + ': Rp' + number_format(val, 0, ',', '.');
            }
          }
        },
        scales: {
          xAxes: [{ gridLines: { display: false } }],
          yAxes: [{ ticks: { beginAtZero: true, callback: v => 'Rp' + number_format(v,0,',','.') } }]
        }
      }
    });
  });
}
*/


let branchSalesChart;

function loadBranchSalesTrend() {
    const params = {
        branch_ids: $('#branch_sales_branches').val() || [],  
        start_date: $('#branch_sales_start').val(),
        end_date:   $('#branch_sales_end').val()
    };

    $.post('{{ route('dashboard.branch-sales-trend') }}', params, function(res) {
        const labels = res.labels || [];
        const series = res.series || [];

        const ctx = document.getElementById('branch_sales_chart').getContext('2d');
        if (branchSalesChart) branchSalesChart.destroy();

        // palet warna untuk 3 garis
        const colors = ['#4e73df', '#e74a3b', '#1cc88a'];

        branchSalesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: series.map(function(s, idx){
                    return {
                        label: s.label,
                        data: s.data,
                        borderColor: colors[idx % colors.length],
                        backgroundColor: 'rgba(0,0,0,0)',
                        fill: false,
                        lineTension: 0,
                        pointRadius: 3
                    };
                })
            },
            options: {
                maintainAspectRatio: false,
                legend: { position: 'bottom' },
                tooltips: {
                    mode: 'index', intersect: false,
                    callbacks: {
                        label: function(tooltipItem, data) {
                            const ds = data.datasets[tooltipItem.datasetIndex];
                            const val = tooltipItem.yLabel;
                            return ds.label + ': Rp' + number_format(val, 0, ',', '.');
                        }
                    }
                },
                scales: {
                    xAxes: [{ gridLines: { display: false } }],
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,
                            callback: function(value){ return 'Rp' + number_format(value, 0, ',', '.'); }
                        }
                    }]
                }
            }
        });
    });
}



            let ordersMonthlyChart;

function loadOrdersMonthly() {
    const params = {
        branch_id:  $('#orders_branch').val(),
        start_date: $('#orders_start').val(),
        end_date:   $('#orders_end').val()
    };

    $.post('{{ route('dashboard.orders-monthly') }}', params, function(rows) {
        const labels  = rows.map(r => r.label);
        const counts  = rows.map(r => r.count);
        const amounts = rows.map(r => r.amount);

        const ctx = document.getElementById('orders_monthly_chart').getContext('2d');
        if (ordersMonthlyChart) ordersMonthlyChart.destroy();

        ordersMonthlyChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Jumlah Order',
                        data: counts,
                        borderColor: '#4e73df',
                        backgroundColor: 'rgba(78,115,223,0.05)',
                        yAxisID: 'yCount',
                        fill: false,
                        lineTension: 0
                    },
                    {
                        label: 'Nominal Order (Rp)',
                        data: amounts,
                        borderColor: '#e74a3b',
                        backgroundColor: 'rgba(231,74,59,0.05)',
                        yAxisID: 'yAmount',
                        fill: false,
                        lineTension: 0
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                legend: { position: 'bottom' },
                tooltips: {
                    mode: 'index',
                    intersect: false,
                    callbacks: {
                        label: function(tooltipItem, data) {
                            const ds = data.datasets[tooltipItem.datasetIndex];
                            const val = tooltipItem.yLabel;
                            if (ds.yAxisID === 'yAmount') {
                                return ds.label + ': Rp' + number_format(val, 0, ',', '.');
                            }
                            return ds.label + ': ' + number_format(val, 0, ',', '.');
                        }
                    }
                },
                scales: {
                    xAxes: [{ gridLines: { display: false } }],
                    yAxes: [
                        {
                            id: 'yCount',
                            position: 'left',
                            ticks: {
                                beginAtZero: true,
                                callback: function(value){ return number_format(value, 0, ',', '.'); }
                            }
                        },
                        {
                            id: 'yAmount',
                            position: 'right',
                            ticks: {
                                beginAtZero: true,
                                callback: function(value){ return 'Rp' + number_format(value, 0, ',', '.'); }
                            },
                            gridLines: { drawOnChartArea: false }
                        }
                    ]
                },
                elements: { point: { radius: 3 } }
            }
        });
    });
}

            Chart.defaults.global.defaultFontFamily = 'Nunito',
                '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
            Chart.defaults.global.defaultFontColor = '#858796';

            function number_format(number, decimals, dec_point, thousands_sep) {
                number = (number + '').replace(',', '').replace(' ', '');
                var n = !isFinite(+number) ? 0 : +number,
                    prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
                    sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
                    dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
                    s = '',
                    toFixedFix = function(n, prec) {
                        var k = Math.pow(10, prec);
                        return '' + Math.round(n * k) / k;
                    };
                s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
                if (s[0].length > 3) {
                    s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
                }
                if ((s[1] || '').length < prec) {
                    s[1] = s[1] || '';
                    s[1] += new Array(prec - s[1].length + 1).join('0');
                }
                return s.join(dec);
            }

          
            function loadProcessFlowMkt5a(startDate = null, endDate = null, branchId = null) {
                const params = {};

                @if(auth()->user()->role?->code === 'super_admin')
                    if (branchId) {
                        params.branch_id = branchId;
                    }
                @else
                    @if(auth()->user()->branch_id)
                        params.branch_id = {{ auth()->user()->branch_id }};
                    @endif
                @endif

                if (startDate) {
                    params.start_date = startDate;
                } else {
                    params.start_date = '{{ now()->startOfYear()->format('Y-m-d') }}';
                }
                
                if (endDate) {
                    params.end_date = endDate;
                } else {
                    params.end_date = '{{ now()->endOfYear()->format('Y-m-d') }}';
                }
                
                console.log('Loading PROCESS FLOW with params:', params);
                
                $.get('/api/dashboard/mkt5a', params)
                    .done(function(response) {
                        $('#all-leads-qty').text(number_format(response.aware.all_leads_qty, 0, ',', '.'));
                        $('#all-leads-time').text('ATR ' + formatTime(response.aware.all_leads_time_avg_hours || 0));
                        
                        $('#acquisition-qty').text(number_format(response.aware.acquisition_in_qty, 0, ',', '.'));
                        $('#acquisition-time').text('ATR ' + formatTime(response.aware.acquisition_time_avg_hours));
                         
                        $('#meeting-qty').text(number_format(response.appeal.meeting_in_qty, 0, ',', '.'));
                        $('#meeting-time').text('ATR ' + formatTime(response.appeal.meeting_time_avg_hours));
                        
                        $('#quotation-qty').text(number_format(response.quotation.quotation_in_qty, 0, ',', '.'));
                        $('#quotation-time').text('ATR ' + formatTime(response.quotation.quotation_time_avg_hours));

                        $('#invoice-qty').text(number_format(response.act.invoice_in_qty, 0, ',', '.'));
                        $('#invoice-time').text('ATR ' + formatTime(response.act.invoice_time_avg_hours));

                        $('#all-leads-pct').text(response.aware.all_leads_percentage + '%');
                        $('#all-leads-acq-pct').text('Total: ' + number_format(response.aware.all_leads_qty, 0, ',', '.'));
                        
                        $('#acquisition-pct').text(response.aware.acquisition_in_percentage + '%');
                        $('#acquisition-cvr').text('Cvr: ' + response.aware.acquisition_conversion_rate + '%');
                        
                        $('#meeting-pct').text(response.appeal.meeting_in_percentage + '%');
                        $('#meeting-my').text('My Leads: ' + number_format(response.appeal.my_leads, 0, ',', '.'));
                        
                        $('#quotation-pct').text(response.quotation.quotation_in_percentage + '%');
                        $('#quotation-amount').text('Rp ' + formatAmount(response.quotation.quotation_in_amount));
                        
                        $('#invoice-pct').text(response.act.invoice_in_percentage + '%');
                        $('#invoice-amount').text('Rp ' + formatAmount(response.act.invoice_in_amount));
                    })
                    .fail(function() {
                        $('#all-leads-qty, #acquisition-qty, #meeting-qty, #quotation-qty, #invoice-qty').text('0');
                        $('#all-leads-time, #acquisition-time, #meeting-time, #quotation-time, #invoice-time').text('ATR 00:00:00');

                        $('#all-leads-pct, #acquisition-pct, #meeting-pct, #quotation-pct, #invoice-pct').text('0%');
                        $('#all-leads-acq-pct').text('Total: 0');
                        $('#acquisition-cvr').text('Cvr: 0%');
                        $('#meeting-my').text('My Leads: 0');
                        $('#quotation-amount, #invoice-amount').text('Rp 0');
                    });
            }

            // Format hours to HH:MM:SS
            function formatTime(hours) {
                if (!hours || hours === 0) return '00:00:00';
                
                const totalSeconds = Math.round(hours * 3600);
                const h = Math.floor(totalSeconds / 3600);
                const m = Math.floor((totalSeconds % 3600) / 60);
                const s = totalSeconds % 60;
                
                return String(h).padStart(2, '0') + ':' + 
                       String(m).padStart(2, '0') + ':' + 
                       String(s).padStart(2, '0');
            }
            
            function formatAmount(amount) {
                if (!amount || amount === 0) return '0';
                
                const num = parseFloat(amount);
                if (num >= 1000000000) {
                    return (num / 1000000000).toFixed(1) + 'M'; // Milyar
                } else if (num >= 1000000) {
                    return (num / 1000000).toFixed(1) + 'Jt'; // Juta
                } else if (num >= 1000) {
                    return (num / 1000).toFixed(1) + 'Rb'; // Ribu
                } else {
                    return number_format(num, 0, ',', '.');
                }
            }

            $(function() {
                const statusMap = {
                    cold: {{ \App\Models\Leads\LeadStatus::COLD }},
                    warm: {{ \App\Models\Leads\LeadStatus::WARM }},
                    hot: {{ \App\Models\Leads\LeadStatus::HOT }}
                };

                const charts = {};

                const defaultStartDate = '{{ now()->startOfYear()->format('Y-m-d') }}';
                const defaultEndDate = '{{ now()->endOfYear()->format('Y-m-d') }}';
                
                loadSourceConversionStats(); 
                $('#source-apply').on('click', loadSourceConversionStats);
                
                $('#svt_apply').on('click', loadAchievementMonthlyPercent);
loadAchievementMonthlyPercent();

// binding & initial load
$('#tvsm_apply').on('click', loadTargetVsSalesMonthly);
loadTargetVsSalesMonthly();


                // binding & initial
$('#donut_apply').on('click', loadSalesAchievementDonuts);
loadSalesAchievementDonuts();
                // bindings
$('#sp_apply').on('click', function(){
  loadSalesPerformanceBar();
  loadSalesAchievementTrend();
});
$('#sa_apply').on('click', loadSalesAchievementTrend);

// initial load
loadSalesPerformanceBar();
loadSalesAchievementTrend();

// === Binding tombol Apply & initial load ===
// Commented out because leads branch trend HTML elements are commented
// $('#cl_apply').on('click', function(){ loadLeadsBranchTrend('cl','cold'); });
// $('#wl_apply').on('click', function(){ loadLeadsBranchTrend('wl','warm'); });
// $('#hl_apply').on('click', function(){ loadLeadsBranchTrend('hl','hot'); });

// initial load - commented out to prevent errors
// loadLeadsBranchTrend('cl','cold');
// loadLeadsBranchTrend('wl','warm');
// loadLeadsBranchTrend('hl','hot');

                $('#branch_sales_apply').on('click', loadBranchSalesTrend);
loadBranchSalesTrend(); // initial (YTD / Top 3)

                $('#orders_apply').on('click', loadOrdersMonthly);
                loadOrdersMonthly();

                function renderChart(ctx, data, label = 'Jumlah Leads', type = 'bar') {
                    let labels = data.map(d => d.source);
                    let values = data.map(d => d.total);
                    if (labels.length === 0) {
                        labels = ['No Data'];
                        values = [0];
                    }
                    const backgroundColors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'];
                    const config = {
                        type: type,
                        data: {
                            labels: labels,
                            datasets: [{
                                label: label,
                                backgroundColor: type === 'pie' ? backgroundColors.slice(0, values.length) : '#4e73df',
                                data: values
                            }]
                        },
                        options: {
                            maintainAspectRatio: false
                        }
                    };

                    if (type === 'bar') {
                        config.options.scales = {
                            yAxes: [{
                                ticks: { beginAtZero: true }
                            }]
                        };
                        config.options.legend = { display: false };
                    } else if (type === 'pie') {
                        config.options.legend = { position: 'bottom' };
                        config.options.tooltips = {
                            callbacks: {
                                label: function(tooltipItem, data) {
                                    const dataset = data.datasets[tooltipItem.datasetIndex];
                                    const total = dataset.data.reduce(function(prev, next) { return prev + next; }, 0);
                                    const value = dataset.data[tooltipItem.index];
                                    const percent = total ? ((value / total) * 100).toFixed(1) : 0;
                                    return data.labels[tooltipItem.index] + ': ' + value + ' (' + percent + '%)';
                                }
                            }
                        };
                    }

                    return new Chart(ctx, config);
                }

                function loadChart(prefix) {
                    const params = {
                        status_id: statusMap[prefix],
                        branch_id: $('#' + prefix + '_branch').val(),
                        start_date: $('#' + prefix + '_start').val(),
                        end_date: $('#' + prefix + '_end').val()
                    };
                    $.post('{{ route('dashboard.group7.leads-source') }}', params, function(data) {
                        const ctx = document.getElementById(prefix + '_chart').getContext('2d');
                        if (charts[prefix]) {
                            charts[prefix].destroy();
                        }
                        charts[prefix] = renderChart(ctx, data);
                    });
                }

                function loadOverviewChart() {
                    const params = {
                        branch_id: $('#overview_branch').val(),
                        start_date: $('#overview_start').val(),
                        end_date: $('#overview_end').val()
                    };
                    $.post('{{ route('dashboard.group3.lead-overview') }}', params, function(data) {
                        const ctx = document.getElementById('overview_chart').getContext('2d');
                        if (charts.overview) {
                            charts.overview.destroy();
                        }
                        charts.overview = renderChart(ctx, data);
                    });
                }

                function loadLeadTotals() {
                    const baseParams = {
                        branch_id: $('#lead_total_branch').val(),
                        start_date: $('#lead_total_start').val(),
                        end_date: $('#lead_total_end').val()
                    };

                    const statuses = ['cold', 'warm', 'hot'];
                    const requests = statuses.map(function(status) {
                        return $.post('{{ route('dashboard.group5.lead-total') }}',
                            Object.assign({
                                status_id: statusMap[status]
                            }, baseParams));
                    });

                    $.when.apply($, requests).done(function() {
                        const responses = arguments.length === 1 ? [arguments] : arguments;
                        const data = statuses.map(function(status, idx) {
                            return {
                                source: status.charAt(0).toUpperCase() + status.slice(1),
                                total: responses[idx][0].total
                            };
                        });
                        const ctx = document.getElementById('lead_total_chart').getContext('2d');
                        if (charts.lead_total) {
                            charts.lead_total.destroy();
                        }
                        charts.lead_total = renderChart(ctx, data);
                    });
                }

                // Commented out - HTML elements not available
                // ['cold', 'warm', 'hot'].forEach(function(prefix) {
                //     $('#' + prefix + '_apply').on('click', function() {
                //         loadChart(prefix);
                //     });
                //     loadChart(prefix);
                // });

                function loadColdWarmChart() {
                    $.post('{{ route('dashboard.group4.cold-warm') }}', function(data) {
                        const ctx = document.getElementById('cw_chart').getContext('2d');
                        if (charts.cw) {
                            charts.cw.destroy();
                        }
                        charts.cw = renderChart(ctx, data, 'Cold to Warm', 'pie');
                    });
                }

                function loadWarmHotChart() {
                    $.post('{{ route('dashboard.group4.warm-hot') }}', function(data) {
                        const ctx = document.getElementById('wh_chart').getContext('2d');
                        if (charts.wh) {
                            charts.wh.destroy();
                        }
                        charts.wh = renderChart(ctx, data, 'Warm to Hot', 'pie');
                    });
                }

                // Commented out - HTML elements not available
                // loadColdWarmChart();
                // loadWarmHotChart();

                // $('#overview_apply').on('click', loadOverviewChart);
                // loadOverviewChart();

                // $('#lead_total_apply').on('click', loadLeadTotals);
                // loadLeadTotals();
                const quotationStatuses = ['review', 'published', 'rejected'];
                let quotationChart;

                function loadQuotationStatusChart() {
                    const baseParams = {
                        branch_id: $('#quotation_branch').val(),
                        start_date: $('#quotation_start').val(),
                        end_date: $('#quotation_end').val()
                    };

                    const requests = quotationStatuses.map(function(status) {
                        return $.post('{{ route('dashboard.group6.quotation-status') }}',
                            Object.assign({
                                status: status
                            }, baseParams));
                    });

                    $.when.apply($, requests).done(function() {
                        const responses = arguments.length === 1 ? [arguments] : arguments;
                        const data = quotationStatuses.map(function(status, idx) {
                            return {
                                source: status.charAt(0).toUpperCase() + status.slice(1),
                                total: responses[idx][0].total
                            };
                        });

                        const ctx = document.getElementById('quotation_chart').getContext('2d');
                        if (quotationChart) {
                            quotationChart.destroy();
                        }
                        quotationChart = renderChart(ctx, data, 'Jumlah Quotation');
                    });
                }


                function loadProcessFlow() {
                    const branchId = {{ Auth::user()->branch_id ?? 'null' }};
                    const apiUrl = '/api/dashboard/mkt5a' + (branchId ? '?branch_id=' + branchId : '');
                    
                    $.get(apiUrl, function(response) {
                        if (response.success) {
                            renderProcessFlow(response.data);
                        }
                    }).fail(function() {
                        console.error('Failed to load process flow data');
                        renderProcessFlowError();
                    });
                }

                function renderProcessFlow(data) {
                    const container = $('#processFlowContainer');
                    container.empty();

                    const firstRow = $('<div class="row mb-3"></div>');
                    data.forEach(function(item, index) {
                        const cardCol = $(`
                            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-3">
                                <div class="process-flow-card">
                                    <div class="process-flow-icon">
                                        <i class="${item.icon}"></i>
                                    </div>
                                    <div class="process-flow-content">
                                        <div class="process-flow-title">${item.title}</div>
                                        <div class="process-flow-count">${number_format(item.count, 0, ',', '.')}</div>
                                        <div class="process-flow-atr">ATR ${item.atr}</div>
                                    </div>
                                </div>
                            </div>
                        `);
                        firstRow.append(cardCol);
                    });

                    const secondRow = $('<div class="row"></div>');
                    data.forEach(function(item, index) {
                        const cardCol = $(`
                            <div class="col-xl-3 col-lg-4 col-md-6 col-sm-12 mb-3">
                                <div class="process-flow-card">
                                    <div class="process-flow-icon">
                                        <i class="${item.icon}"></i>
                                    </div>
                                    <div class="process-flow-content">
                                        <div class="process-flow-title">${item.title}</div>
                                        <div class="process-flow-count">${item.percentage}%</div>
                                        <div class="process-flow-atr">ATR ${item.atr}</div>
                                    </div>
                                </div>
                            </div>
                        `);
                        secondRow.append(cardCol);
                    });

                    container.append(firstRow);
                    container.append(secondRow);
                }

                function renderProcessFlowError() {
                    const container = $('#processFlowContainer');
                    container.html(`
                        <div class="col-12">
                            <div class="alert alert-warning" role="alert">
                                <i class="fas fa-exclamation-triangle"></i>
                                Unable to load process flow data. Please try again later.
                            </div>
                        </div>
                    `);
                }

                loadProcessFlow();
                setInterval(loadProcessFlow, 300000);

                function loadSourceConversionStats() {
                    const params = {};
                    
                    @if(auth()->user()->role?->code === 'super_admin')
                        const branchId = $('#source-branch').val();
                        if (branchId && branchId !== '') {
                            params.branch_id = branchId;
                        }
                    @else
                        @if(auth()->user()->branch_id)
                            params.branch_id = {{ auth()->user()->branch_id }};
                        @endif
                    @endif
                    
                    const sourceFilter = $('#source-filter').val();
                    const startDate = $('#source-start-date').val();
                    const endDate = $('#source-end-date').val();
                    
                    if (sourceFilter) params.source = sourceFilter;
                    if (startDate) params.start_date = startDate;
                    if (endDate) params.end_date = endDate;

                    const tbody = $('#source-conversion-tbody');
                    tbody.html(`
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="spinner-border text-success" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2 mb-0 text-muted">Loading source conversion data...</p>
                            </td>
                        </tr>
                    `);

                    console.log('Loading source conversion with params:', params);

                    console.log('Also updating PROCESS FLOW with filters - Branch:', params.branch_id, 'Date range:', startDate, 'to', endDate);
                    loadProcessFlowMkt5a(startDate, endDate, params.branch_id);
                    
                    $.get('/api/dashboard/source-conversion-stats', params)
                        .done(function(response) {
                            console.log('Source conversion response:', response);
                            if (response.data && response.data.length > 0) {
                                renderSourceConversionTable(response.data);
                            } else {
                                tbody.html(`
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <i class="fas fa-info-circle text-muted mb-2" style="font-size: 2rem;"></i>
                                            <p class="mb-0 text-muted">No data available for the selected period</p>
                                        </td>
                                    </tr>
                                `);

                                $('#total-cum').text('0');
                                $('#total-cold').text('0');
                                $('#total-warm').text('0');
                                $('#total-hot').text('0');
                                $('#total-deal').text('0');
                                $('#total-all').text('0');

                                adjustSourceConversionHeight(0);
                            }
                        })
                        .fail(function(xhr) {
                            console.error('Failed to load source conversion data:', xhr);
                            tbody.html(`
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="fas fa-exclamation-triangle text-warning mb-2" style="font-size: 2rem;"></i>
                                        <p class="mb-0 text-muted">Failed to load data. Please try again.</p>
                                        <button class="btn btn-sm btn-outline-primary mt-2" onclick="loadSourceConversionStats()">
                                            <i class="fas fa-refresh me-1"></i> Retry
                                        </button>
                                    </td>
                                </tr>
                            `);
                            $('#total-cum').text('0');
                            $('#total-cold').text('0');
                            $('#total-warm').text('0');
                            $('#total-hot').text('0');
                            $('#total-deal').text('0');
                            $('#total-all').text('0');

                            adjustSourceConversionHeight(0);
                        });
                }

                function adjustPotentialBranchHeight(rowCount) {
                    const container = $('#potential-branch-container');

                    container.removeClass('compact');
                    
                    if (rowCount === 0) {
                        container.addClass('compact');
                    } else if (rowCount <= 3) {
                        container.addClass('compact');
                    } else {
                        container.removeClass('compact');
                    }
                }

                function adjustPotentialListHeight(rowCount) {
                    const container = $('#potential-list-container');

                    container.removeClass('compact');
                    
                    if (rowCount === 0) {
                        container.addClass('compact');
                    } else if (rowCount <= 3) {
                        container.addClass('compact');
                    } else {
                        container.removeClass('compact');
                    }
                }

                function renderSourceConversionTable(data) {
                    const tbody = $('#source-conversion-tbody');
                    tbody.empty();

                    let totals = {
                        cumulative: 0,
                        cold: 0,
                        warm: 0,
                        hot: 0,
                        deal: 0
                    };

                    data.forEach(function(item) {
                        totals.cumulative += item.cumulative || 0;
                        totals.cold += item.cold || 0;
                        totals.warm += item.warm || 0;
                        totals.hot += item.hot || 0;
                        totals.deal += item.deal || 0;
                    });

                    data.forEach(function(item) {
                        const rowTotal = (item.cumulative || 0) + (item.cold || 0) + (item.warm || 0) + (item.hot || 0) + (item.deal || 0);
                        
                        const row = $(`
                            <tr>
                                <td>
                                    <span class="source-badge">${item.source}</span>
                                </td>
                                <td class="text-center conversion-number">
                                    ${(item.cumulative || 0).toLocaleString()} 
                                    <span class="cumulative-percentage">(${(item.cumulative_percentage || 0).toFixed(1)}%)</span>
                                </td>
                                <td class="text-center">
                                    ${(item.cold || 0).toLocaleString()} 
                                    <span class="cumulative-percentage">(${(item.cold_percentage || 0).toFixed(1)}%)</span>
                                </td>
                                <td class="text-center">
                                    ${(item.warm || 0).toLocaleString()} 
                                    <span class="cumulative-percentage">(${(item.warm_percentage || 0).toFixed(1)}%)</span>
                                </td>
                                <td class="text-center">
                                    ${(item.hot || 0).toLocaleString()} 
                                    <span class="cumulative-percentage">(${(item.hot_percentage || 0).toFixed(1)}%)</span>
                                </td>
                                <td class="text-center conversion-number">
                                    ${(item.deal || 0).toLocaleString()} 
                                    <span class="cumulative-percentage">(${(item.deal_percentage || 0).toFixed(1)}%)</span>
                                </td>
                                <td class="text-center" style="background-color: rgba(17, 86, 65, 0.1); font-weight: bold;">
                                    ${rowTotal.toLocaleString()}
                                </td>
                            </tr>
                        `);
                        tbody.append(row);
                    });

                    const grandTotal = totals.cumulative + totals.cold + totals.warm + totals.hot + totals.deal;
                    $('#total-cum').text(totals.cumulative.toLocaleString());
                    $('#total-cold').text(totals.cold.toLocaleString());
                    $('#total-warm').text(totals.warm.toLocaleString());
                    $('#total-hot').text(totals.hot.toLocaleString());
                    $('#total-deal').text(totals.deal.toLocaleString());
                    $('#total-all').text(grandTotal.toLocaleString());
                    
                    adjustSourceConversionHeight(data.length);
                }
                
                function adjustSourceConversionHeight(rowCount) {
                    const container = $('#source-conversion-container');

                    container.removeClass('compact');
                    
                    if (rowCount === 0) {
                        container.addClass('compact');
                    } else if (rowCount <= 3) {
                        container.addClass('compact');
                    } else {
                        container.removeClass('compact');
                    }
                }



                let sourceMonitoringChart;

                function loadSourceMonitoringStats() {
                    const params = {
                        year: $('#source-monitoring-year').val() || new Date().getFullYear()
                    };

                    @if(auth()->user()->role?->code !== 'super_admin')
                        params.branch_id = {{ auth()->user()->branch_id ?? 'null' }};
                    @else
                        const branchId = $('#source-monitoring-branch').val();
                        if (branchId && branchId !== '') {
                            params.branch_id = branchId;
                        }
                    @endif

                    console.log('Loading source monitoring chart with params: ', params);

                    const chartContainer = $('#source-monitoring-chart').parent();
                    if (sourceMonitoringChart) {
                        sourceMonitoringChart.destroy();
                        sourceMonitoringChart = null;
                    }
                    
                    const ctx = document.getElementById('source-monitoring-chart').getContext('2d');
                    ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
                    ctx.fillStyle = '#6c757d';
                    ctx.font = '14px Arial';
                    ctx.textAlign = 'center';
                    ctx.fillText('Loading chart data...', ctx.canvas.width / 2, ctx.canvas.height / 2);

                    $.get('/api/dashboard/source-monthly-stats', params)
                        .done(function(res) {
                        console.log('Source monitoring chart response: ', res);
                        
                        const labels = res.month_labels || [];
                        const data = res.data || [];

                        const datasets = data.map((source, index) => ({
                            label: source.source,
                            data: source.months || [],
                            borderColor: getChartColor(index),
                            backgroundColor: getChartColor(index, 0.1),
                            fill: false,
                            tension: 0.4,
                            pointRadius: 4,
                            pointHoverRadius: 6,
                            pointBackgroundColor: getChartColor(index),
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            borderWidth: 2
                        }));

                        const ctx = document.getElementById('source-monitoring-chart').getContext('2d');
                        
                        if (sourceMonitoringChart) {
                            sourceMonitoringChart.destroy();
                        }

                        sourceMonitoringChart = new Chart(ctx, {
                            type: 'line',
                            data: { labels, datasets },
                            options: {
                                maintainAspectRatio: false,
                                responsive: true,
                                plugins: {
                                    legend: {
                                        display: true,
                                        position: 'bottom',
                                        labels: {
                                            usePointStyle: true,
                                            padding: 15,
                                            font: {
                                                size: 11,
                                                weight: '500'
                                            },
                                            color: '#374151'
                                        }
                                    },
                                    tooltip: {
                                        mode: 'index', 
                                        intersect: false,
                                        backgroundColor: 'rgba(17, 86, 65, 0.95)',
                                        titleColor: '#fff',
                                        bodyColor: '#fff',
                                        borderColor: '#115641',
                                        borderWidth: 1,
                                        cornerRadius: 8,
                                        padding: 10,
                                        titleFont: {
                                            size: 13,
                                            weight: 'bold'
                                        },
                                        bodyFont: {
                                            size: 12
                                        },
                                        callbacks: {
                                            title: function(context) {
                                                return 'Month: ' + context[0].label;
                                            },
                                            label: function(context) {
                                                const label = context.dataset.label || '';
                                                const value = context.parsed.y || 0;
                                                return '  ' + label + ': ' + value.toLocaleString() + ' leads';
                                            }
                                        }
                                    }
                                },
                                scales: {
                                    x: {
                                        grid: {
                                            display: false
                                        },
                                        ticks: {
                                            color: '#6B7280',
                                            font: {
                                                size: 11,
                                                weight: '500'
                                            }
                                        }
                                    },
                                    y: {
                                        beginAtZero: true,
                                        grid: {
                                            color: '#F3F4F6',
                                            drawBorder: false
                                        },
                                        ticks: {
                                            color: '#6B7280',
                                            font: {
                                                size: 11,
                                                weight: '500'
                                            },
                                            callback: function(value) { 
                                                return value.toLocaleString(); 
                                            }
                                        }
                                    }
                                },
                                elements: {
                                    point: {
                                        hoverBorderWidth: 3
                                    }
                                }
                            }
                        });

                    }).fail(function(xhr, status, error) {
                        console.error('Error loading source monitoring chart:', error);
                    });
                }

                function loadSourceMonitoringTable() {
                    const params = {
                        year: $('#source-monitoring-table-year').val() || new Date().getFullYear()
                    };

                    @if(auth()->user()->role?->code !== 'super_admin')
                        params.branch_id = {{ auth()->user()->branch_id ?? 'null' }};
                    @else
                        const branchId = $('#source-monitoring-table-branch').val();
                        if (branchId && branchId !== '') {
                            params.branch_id = branchId;
                        }
                    @endif

                    const sourceFilter = $('#source-monitoring-source-filter').val();
                    if (sourceFilter && sourceFilter !== '') {
                        params.source = sourceFilter;
                    }

                    console.log('Loading source monitoring table with params: ', params);

                    const tbody = $('#source-monitoring-tbody');
                    tbody.html(`
                        <tr>
                            <td colspan="14" class="text-center py-4">
                                <div class="text-success">
                                    <div class="spinner-border text-success" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-3 mb-0 text-muted">Loading source monitoring data...</p>
                                </div>
                            </td>
                        </tr>
                    `);

                    $.get('/api/dashboard/source-monthly-stats', params)
                        .done(function(res) {
                        console.log('Source monitoring table response: ', res);
                        
                        tbody.empty();
                        
                        if (res.data && res.data.length > 0) {

                            const monthlyTotals = new Array(12).fill(0);
                            let grandTotal = 0;

                            res.data.forEach(function(item) {
                                const months = item.months || [];
                                for (let i = 0; i < 12; i++) {
                                    monthlyTotals[i] += months[i] || 0;
                                }
                                grandTotal += item.total || 0;

                                const row = $(`
                                    <tr class="source-conversion-row">
                                        <td class="py-2 px-2" style="font-size: 11px; min-width: 120px;">
                                            <a href="#" class="source-link">${item.source || 'Unknown'}</a>
                                        </td>
                                        <td class="text-center py-2 px-1" style="font-size: 10px;">${(months[0] || 0).toLocaleString()}</td>
                                        <td class="text-center py-2 px-1" style="font-size: 10px;">${(months[1] || 0).toLocaleString()}</td>
                                        <td class="text-center py-2 px-1" style="font-size: 10px;">${(months[2] || 0).toLocaleString()}</td>
                                        <td class="text-center py-2 px-1" style="font-size: 10px;">${(months[3] || 0).toLocaleString()}</td>
                                        <td class="text-center py-2 px-1" style="font-size: 10px;">${(months[4] || 0).toLocaleString()}</td>
                                        <td class="text-center py-2 px-1" style="font-size: 10px;">${(months[5] || 0).toLocaleString()}</td>
                                        <td class="text-center py-2 px-1" style="font-size: 10px;">${(months[6] || 0).toLocaleString()}</td>
                                        <td class="text-center py-2 px-1" style="font-size: 10px;">${(months[7] || 0).toLocaleString()}</td>
                                        <td class="text-center py-2 px-1" style="font-size: 10px;">${(months[8] || 0).toLocaleString()}</td>
                                        <td class="text-center py-2 px-1" style="font-size: 10px;">${(months[9] || 0).toLocaleString()}</td>
                                        <td class="text-center py-2 px-1" style="font-size: 10px;">${(months[10] || 0).toLocaleString()}</td>
                                        <td class="text-center py-2 px-1" style="font-size: 10px;">${(months[11] || 0).toLocaleString()}</td>
                                        <td class="text-center py-2 px-1 conversion-number" style="font-size: 10px; min-width: 60px;"><strong>${(item.total || 0).toLocaleString()}</strong></td>
                                    </tr>
                                `);
                                tbody.append(row);
                            });

                            $('#total-jan').text(monthlyTotals[0].toLocaleString());
                            $('#total-feb').text(monthlyTotals[1].toLocaleString());
                            $('#total-mar').text(monthlyTotals[2].toLocaleString());
                            $('#total-apr').text(monthlyTotals[3].toLocaleString());
                            $('#total-may').text(monthlyTotals[4].toLocaleString());
                            $('#total-jun').text(monthlyTotals[5].toLocaleString());
                            $('#total-jul').text(monthlyTotals[6].toLocaleString());
                            $('#total-aug').text(monthlyTotals[7].toLocaleString());
                            $('#total-sep').text(monthlyTotals[8].toLocaleString());
                            $('#total-oct').text(monthlyTotals[9].toLocaleString());
                            $('#total-nov').text(monthlyTotals[10].toLocaleString());
                            $('#total-dec').text(monthlyTotals[11].toLocaleString());
                            $('#total-year').text(grandTotal.toLocaleString());


                        } else {
                            tbody.html(`
                                <tr>
                                    <td colspan="14" class="text-center py-4 text-muted">
                                        <i class="fas fa-info-circle mb-2" style="font-size: 1.5rem;"></i><br>
                                        <span>No source monitoring data available for the selected period</span>
                                    </td>
                                </tr>
                            `);

                            $('#total-jan, #total-feb, #total-mar, #total-apr, #total-may, #total-jun, #total-jul, #total-aug, #total-sep, #total-oct, #total-nov, #total-dec, #total-year').text('0');
                        }

                    }).fail(function(xhr, status, error) {
                        console.error('Error loading source monitoring table:', error);
                        tbody.html(`
                            <tr>
                                <td colspan="14" class="text-center py-4 text-danger">
                                    <i class="fas fa-exclamation-circle mb-2" style="font-size: 1.5rem;"></i><br>
                                    <span>Error loading source monitoring data</span>
                                    <br><button class="btn btn-sm btn-outline-primary mt-3" onclick="loadSourceMonitoringTable()">
                                        <i class="fas fa-refresh me-1"></i> Retry
                                    </button>
                                </td>
                            </tr>
                        `);
                    });
                }

                function getChartColor(index, alpha = 1) {
                    const colors = [
                        '#115641', '#F97316', '#3B82F6', '#EF4444', '#10B981', 
                        '#8B5CF6', '#F59E0B', '#EC4899', '#6B7280', '#84CC16',
                        '#06B6D4', '#F43F5E', '#8B5A2B', '#6366F1'
                    ];
                    const color = colors[index % colors.length];
                    
                    if (alpha < 1) {
                        const r = parseInt(color.slice(1, 3), 16);
                        const g = parseInt(color.slice(3, 5), 16);
                        const b = parseInt(color.slice(5, 7), 16);
                        return `rgba(${r}, ${g}, ${b}, ${alpha})`;
                    }
                    
                    return color;
                }

                $('#source-monitoring-apply').on('click', function() {
                    @if(auth()->user()->role?->code === 'super_admin')
                        $('#source-monitoring-table-branch').val($('#source-monitoring-branch').val());
                    @endif
                    $('#source-monitoring-table-year').val($('#source-monitoring-year').val());
                    
                    loadSourceMonitoringStats();
                    loadSourceMonitoringTable();
                });

                $('#source-monitoring-table-apply').on('click', function() {
                    // Sync chart filters with table filters
                    @if(auth()->user()->role?->code === 'super_admin')
                        $('#source-monitoring-branch').val($('#source-monitoring-table-branch').val());
                    @endif
                    $('#source-monitoring-year').val($('#source-monitoring-table-year').val());
                    
                    loadSourceMonitoringStats();
                    loadSourceMonitoringTable();
                });

                loadSourceMonitoringStats();
                loadSourceMonitoringTable();


                // ==================== SLS DEALING FUNCTIONS ====================
                let slsDealingChart;

                // Helper function to format amount in short form (M, Jt, Rb)
                function formatAmountShort(amount) {
                    if (!amount || amount === 0) return '0';
                    
                    const num = parseFloat(amount);
                    if (num >= 1000000000) {
                        return (num / 1000000000).toFixed(1) + 'M'; // Milyar
                    } else if (num >= 1000000) {
                        return (num / 1000000).toFixed(1) + 'Jt'; // Juta
                    } else if (num >= 1000) {
                        return (num / 1000).toFixed(1) + 'Rb'; // Ribu
                    } else {
                        return num.toLocaleString();
                    }
                }

                function loadSlsDealingChart() {
                    const params = {};

                    @if(auth()->user()->role?->code !== 'super_admin')
                        params.branch_id = {{ auth()->user()->branch_id ?? 'null' }};
                    @else
                        const branchId = $('#sls-dealing-branch').val();
                        if (branchId && branchId !== '') {
                            params.branch_id = branchId;
                        }
                    @endif

                    const startDate = $('#sls-dealing-start-date').val();
                    const endDate = $('#sls-dealing-end-date').val();
                    
                    if (startDate) params.start_date = startDate;
                    if (endDate) params.end_date = endDate;

                    console.log('Loading SLS dealing chart with params: ', params);

                    const chartContainer = $('#sls-dealing-chart').parent();
                    if (slsDealingChart) {
                        slsDealingChart.destroy();
                        slsDealingChart = null;
                    }
                    
                    const ctx = document.getElementById('sls-dealing-chart').getContext('2d');
                    ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
                    ctx.fillStyle = '#6c757d';
                    ctx.font = '14px Arial';
                    ctx.textAlign = 'center';
                    ctx.fillText('Loading chart data...', ctx.canvas.width / 2, ctx.canvas.height / 2);

                    $.get('/api/dashboard/dealing-list', params)
                        .done(function(res) {
                        console.log('SLS dealing chart response: ', res);
                        
                        if (!res.monthly_data || res.monthly_data.length === 0) {
                            const ctx = document.getElementById('sls-dealing-chart').getContext('2d');
                            ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
                            ctx.fillStyle = '#6c757d';
                            ctx.font = '14px Arial';
                            ctx.textAlign = 'center';
                            ctx.fillText('No data available', ctx.canvas.width / 2, ctx.canvas.height / 2);
                            return;
                        }

                        const labels = res.monthly_data.map(month => month.month_name) || [];
                        const achievementData = res.monthly_data.map(month => month.total_achievement_amount) || [];
                        const unitSalesData = res.monthly_data.map(month => month.total_unit_sales) || [];
                        
                        // Store monthly data for tooltip reference
                        const monthlyDataRef = res.monthly_data;

                        // Initialize chart
                        const ctx = document.getElementById('sls-dealing-chart').getContext('2d');
                        
                        if (slsDealingChart) {
                            slsDealingChart.destroy();
                        }

                        slsDealingChart = new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: labels,
                                datasets: [
                                    {
                                        label: 'Achievement Amount',
                                        data: achievementData,
                                        borderColor: '#115641',
                                        backgroundColor: 'rgba(17, 86, 65, 0.1)',
                                        fill: false,
                                        lineTension: 0.4,
                                        pointRadius: 4,
                                        pointHoverRadius: 6,
                                        pointBackgroundColor: '#115641',
                                        pointBorderColor: '#fff',
                                        pointBorderWidth: 2,
                                        borderWidth: 2,
                                        yAxisID: 'yAmount'
                                    },
                                    {
                                        label: 'Unit Sales',
                                        data: unitSalesData,
                                        borderColor: '#F97316',
                                        backgroundColor: 'rgba(249, 115, 22, 0.1)',
                                        fill: false,
                                        lineTension: 0.4,
                                        pointRadius: 4,
                                        pointHoverRadius: 6,
                                        pointBackgroundColor: '#F97316',
                                        pointBorderColor: '#fff',
                                        pointBorderWidth: 2,
                                        borderWidth: 2,
                                        yAxisID: 'yUnit'
                                    }
                                ]
                            },
                            options: {
                                maintainAspectRatio: false,
                                responsive: true,
                                legend: {
                                    display: true,
                                    position: 'bottom',
                                    labels: {
                                        usePointStyle: true,
                                        padding: 15
                                    }
                                },
                                tooltips: {
                                    mode: 'index', 
                                    intersect: false,
                                    backgroundColor: 'rgba(17, 86, 65, 0.95)',
                                    titleFontColor: '#fff',
                                    bodyFontColor: '#fff',
                                    borderColor: '#115641',
                                    borderWidth: 1,
                                    cornerRadius: 8,
                                    titleFontSize: 13,
                                    bodyFontSize: 11,
                                    bodySpacing: 4,
                                    xPadding: 12,
                                    yPadding: 12,
                                    callbacks: {
                                        title: function(tooltipItems, data) {
                                            return 'Month: ' + tooltipItems[0].label;
                                        },
                                        label: function(tooltipItem, data) {
                                            const dataset = data.datasets[tooltipItem.datasetIndex];
                                            const label = dataset.label || '';
                                            const value = tooltipItem.yLabel || 0;
                                            if (dataset.yAxisID === 'yAmount') {
                                                return '  ' + label + ': Rp' + formatAmountShort(value);
                                            } else {
                                                return '  ' + label + ': ' + value.toLocaleString() + ' units';
                                            }
                                        },
                                        afterBody: function(tooltipItems, data) {
                                            if (tooltipItems.length > 0) {
                                                const monthIndex = tooltipItems[0].index;
                                                const monthData = monthlyDataRef[monthIndex];
                                                
                                                if (monthData && monthData.sales_data) {
                                                    // Filter sales yang memiliki achievement atau unit sales > 0
                                                    const activeSales = monthData.sales_data.filter(sales => 
                                                        (sales.achievement_amount > 0) || (sales.unit_sales > 0)
                                                    );
                                                    
                                                    if (activeSales.length > 0) {
                                                        let salesInfo = ['', '--- Sales Contributors ---'];
                                                        activeSales.forEach(sales => {
                                                            let salesLine = '• ' + sales.sales_name;
                                                            const details = [];
                                                            
                                                            if (sales.achievement_amount > 0) {
                                                                details.push('Rp' + formatAmountShort(sales.achievement_amount));
                                                            }
                                                            if (sales.unit_sales > 0) {
                                                                details.push(sales.unit_sales + ' units');
                                                            }
                                                            
                                                            if (details.length > 0) {
                                                                salesLine += ' (' + details.join(', ') + ')';
                                                            }
                                                            
                                                            salesInfo.push(salesLine);
                                                        });
                                                        return salesInfo.join('\n');
                                                    } else {
                                                        return '\n\n--- No Sales Activity ---';
                                                    }
                                                }
                                            }
                                            return '';
                                        }
                                    }
                                },
                                scales: {
                                    xAxes: [{
                                        gridLines: {
                                            display: false
                                        },
                                        ticks: {
                                            fontColor: '#6B7280',
                                            fontSize: 11
                                        }
                                    }],
                                    yAxes: [
                                        {
                                            id: 'yAmount',
                                            type: 'linear',
                                            position: 'left',
                                            ticks: {
                                                beginAtZero: true,
                                                fontColor: '#6B7280',
                                                fontSize: 11,
                                                callback: function(value) { 
                                                    return 'Rp' + formatAmountShort(value);
                                                }
                                            },
                                            gridLines: {
                                                color: '#F3F4F6',
                                                drawBorder: false
                                            }
                                        },
                                        {
                                            id: 'yUnit',
                                            type: 'linear',
                                            position: 'right',
                                            ticks: {
                                                beginAtZero: true,
                                                fontColor: '#6B7280',
                                                fontSize: 11,
                                                callback: function(value) { 
                                                    return value.toLocaleString();
                                                }
                                            },
                                            gridLines: {
                                                drawOnChartArea: false
                                            }
                                        }
                                    ]
                                },
                                elements: {
                                    point: {
                                        hoverBorderWidth: 3
                                    }
                                }
                            }
                        });

                    }).fail(function(xhr, status, error) {
                        console.error('Error loading SLS dealing chart:', error);
                        const ctx = document.getElementById('sls-dealing-chart').getContext('2d');
                        ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);
                        ctx.fillStyle = '#dc3545';
                        ctx.font = '14px Arial';
                        ctx.textAlign = 'center';
                        ctx.fillText('Error loading chart data', ctx.canvas.width / 2, ctx.canvas.height / 2);
                    });
                }

                function loadSlsDealingTable() {
                    const params = {};

                    @if(auth()->user()->role?->code !== 'super_admin')
                        params.branch_id = {{ auth()->user()->branch_id ?? 'null' }};
                    @else
                        const branchId = $('#sls-dealing-table-branch').val();
                        if (branchId && branchId !== '') {
                            params.branch_id = branchId;
                        }
                    @endif

                    const startDate = $('#sls-dealing-table-start-date').val();
                    const endDate = $('#sls-dealing-table-end-date').val();
                    
                    if (startDate) params.start_date = startDate;
                    if (endDate) params.end_date = endDate;

                    console.log('Loading SLS dealing table with params: ', params);

                    const tbody = $('#sls-dealing-tbody');
                    tbody.html(`
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="text-success">
                                    <div class="spinner-border text-success" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-3 mb-0 text-muted">Loading SLS dealing data...</p>
                                </div>
                            </td>
                        </tr>
                    `);

                    $.get('/api/dashboard/dealing-list', params)
                        .done(function(res) {
                        console.log('SLS dealing table response: ', res);
                        
                        tbody.empty();
                        
                        if (res.data && res.data.length > 0) {
                            // Calculate totals
                            let totalTargetAmount = 0;
                            let totalAcvAmount = 0;
                            let totalUnitSales = 0;

                            // Render data rows and calculate totals
                            res.data.forEach(function(item) {
                                totalTargetAmount += item.target_amount || 0;
                                totalAcvAmount += item.achievement_amount || 0;
                                totalUnitSales += item.unit_sales || 0;

                                const row = $(`
                                    <tr class="source-conversion-row">
                                        <td class="py-2 px-2" style="font-size: 11px; min-width: 120px;">
                                            <a href="#" class="source-link">${item.nama_sales || 'Unknown'}</a>
                                        </td>
                                        <td class="text-center py-2 px-1" style="font-size: 10px;">Rp${formatAmountShort(item.target_amount || 0)}</td>
                                        <td class="text-center py-2 px-1" style="font-size: 10px;">Rp${formatAmountShort(item.achievement_amount || 0)}</td>
                                        <td class="text-center py-2 px-1" style="font-size: 10px;">${(item.achievement_percentage || 0).toFixed(1)}%</td>
                                        <td class="text-center py-2 px-1" style="font-size: 10px;">${(item.unit_sales || 0).toLocaleString()}</td>
                                        <td class="text-center py-2 px-1" style="font-size: 10px;">${item.branch || '-'}</td>
                                        <td class="text-center py-2 px-1" style="font-size: 10px;">${item.periode || '-'}</td>
                                    </tr>
                                `);
                                tbody.append(row);
                            });

                            // Calculate overall percentage
                            const totalPercentage = totalTargetAmount > 0 ? (totalAcvAmount / totalTargetAmount * 100) : 0;

                            // Update sticky footer totals
                            $('#total-target-amount').text('Rp' + formatAmountShort(totalTargetAmount));
                            $('#total-acv-amount').text('Rp' + formatAmountShort(totalAcvAmount));
                            $('#total-percentage').text(totalPercentage.toFixed(1) + '%');
                            $('#total-unit-sales').text(totalUnitSales.toLocaleString());
                        } else {
                            tbody.html(`
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <i class="fas fa-info-circle mb-2" style="font-size: 1.5rem;"></i><br>
                                        <span>No SLS dealing data available for the selected period</span>
                                    </td>
                                </tr>
                            `);
                            
                            // Reset sticky footer totals
                            $('#total-target-amount, #total-acv-amount').text('Rp0');
                            $('#total-percentage').text('0%');
                            $('#total-unit-sales').text('0');
                        }

                    }).fail(function(xhr, status, error) {
                        console.error('Error loading SLS dealing table:', error);
                        tbody.html(`
                            <tr>
                                <td colspan="7" class="text-center py-4 text-danger">
                                    <i class="fas fa-exclamation-circle mb-2" style="font-size: 1.5rem;"></i><br>
                                    <span>Error loading SLS dealing data</span>
                                    <br><button class="btn btn-sm btn-outline-primary mt-3" onclick="loadSlsDealingTable()">
                                        <i class="fas fa-refresh me-1"></i> Retry
                                    </button>
                                </td>
                            </tr>
                        `);
                        
                        // Reset sticky footer totals on error
                        $('#total-target-amount, #total-acv-amount').text('Rp0');
                        $('#total-percentage').text('0%');
                        $('#total-unit-sales').text('0');
                    });
                }

                // SLS Potential Dealing Branch Table Functions
                function loadPotentialBranchTable() {
                    const params = {
                        branch_id: $('#potential-branch-branch').val() || null,
                        start_date: $('#potential-branch-start-date').val(),
                        end_date: $('#potential-branch-end-date').val()
                    };

                    console.log('Loading potential branch with params:', params);

                    const tbody = $('#potential-branch-tbody');
                    
                    // Show loading state
                    tbody.html(`
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="text-success">
                                    <div class="spinner-border text-success" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2 mb-0 text-muted">Loading potential dealing branch data...</p>
                                </div>
                            </td>
                        </tr>
                    `);

                    $.ajax({
                        url: '/api/dashboard/warm-hot-list',
                        method: 'GET',
                        data: params,
                        dataType: 'json'
                    }).done(function(response) {
                        console.log('Potential branch response:', response);
                        tbody.empty();

                        if (response.success && response.data && response.data.length > 0) {
                            let totalWarmHotAmount = 0;
                            let totalWarmHotQty = 0;
                            let totalAvgDiscount = 0;
                            let count = 0;

                            response.data.forEach(function(item) {
                                totalWarmHotAmount += parseFloat(item.warm_hot_amount || 0);
                                totalWarmHotQty += parseInt(item.warm_hot_qty || 0);
                                totalAvgDiscount += parseFloat(item.avg_discount || 0);
                                count++;

                                const row = $(`
                                    <tr class="potential-branch-row">
                                        <td class="py-3 px-4">
                                            <a href="#" class="source-link">${item.nama_sales || 'Unknown'}</a>
                                        </td>
                                        <td class="text-center py-3 px-4">Rp${formatAmountShort(item.warm_hot_amount || 0)}</td>
                                        <td class="text-center py-3 px-4">${(item.warm_hot_qty || 0).toLocaleString()}</td>
                                        <td class="text-center py-3 px-4">${(item.avg_discount || 0).toFixed(2)}%</td>
                                        <td class="text-center py-3 px-4">${item.branch || '-'}</td>
                                        <td class="text-center py-3 px-4" style="background-color: #f8f9fa;">${item.periode || '-'}</td>
                                    </tr>
                                `);
                                tbody.append(row);
                            });

                            // Calculate average discount
                            const avgDiscount = count > 0 ? (totalAvgDiscount / count) : 0;

                            // Update sticky footer totals
                            $('#total-branch-warm-hot-amount').text('Rp' + formatAmountShort(totalWarmHotAmount));
                            $('#total-branch-warm-hot-qty').text(totalWarmHotQty.toLocaleString());
                            $('#total-branch-avg-discount').text(avgDiscount.toFixed(2) + '%');
                            
                            // Adjust table height based on content
                            adjustPotentialBranchHeight(response.data.length);
                        } else {
                            tbody.html(`
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-info-circle mb-2" style="font-size: 1.5rem;"></i><br>
                                        <span>No potential dealing branch data available for the selected period</span>
                                    </td>
                                </tr>
                            `);
                            
                            // Reset totals
                            $('#total-branch-warm-hot-amount').text('Rp0');
                            $('#total-branch-warm-hot-qty').text('0');
                            $('#total-branch-avg-discount').text('0%');
                            
                            // Adjust table height for empty state
                            adjustPotentialBranchHeight(0);
                        }

                    }).fail(function(xhr, status, error) {
                        console.error('Error loading potential branch table:', error);
                        tbody.html(`
                            <tr>
                                <td colspan="6" class="text-center py-5 text-danger">
                                    <i class="fas fa-exclamation-circle mb-2" style="font-size: 1.5rem;"></i><br>
                                    <span>Error loading potential dealing branch data</span>
                                    <br><button class="btn btn-sm btn-outline-primary mt-3" onclick="loadPotentialBranchTable()">
                                        <i class="fas fa-refresh me-1"></i> Retry
                                    </button>
                                </td>
                            </tr>
                        `);
                        
                        // Reset totals on error
                        $('#total-branch-warm-hot-amount').text('Rp0');
                        $('#total-branch-warm-hot-qty').text('0');
                        $('#total-branch-avg-discount').text('0%');
                    });
                }

                function loadPotentialListTable() {
                    const params = {
                        branch_id: $('#potential-list-branch').val() || null,
                        start_date: $('#potential-list-start-date').val(),
                        end_date: $('#potential-list-end-date').val()
                    };

                    console.log('Loading potential list with params:', params);

                    const tbody = $('#potential-list-tbody');

                    tbody.html(`
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-success">
                                    <div class="spinner-border text-success" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                    <p class="mt-2 mb-0 text-muted">Loading potential dealing list data...</p>
                                </div>
                            </td>
                        </tr>
                    `);

                    $.ajax({
                        url: '/api/dashboard/potential-dealing',
                        method: 'GET',
                        data: params,
                        dataType: 'json'
                    }).done(function(response) {
                        console.log('Potential list response:', response);
                        tbody.empty();

                        if (response.success && response.data && response.data.length > 0) {
                            let totalPotential = 0;
                            let totalAmount = 0;

                            response.data.forEach(function(item) {
                                totalPotential++;
                                totalAmount += parseFloat(item.amount || 0);

                                const lastActivity = item.last_activity ? new Date(item.last_activity).toLocaleDateString('id-ID') : '-';

                                const statusClass = item.status === 'Hot' ? 'badge-danger' : 'badge-warning';
                                const statusBadge = `<span class="badge ${statusClass}">${item.status}</span>`;

                                const validationClass = item.data_validation === 'complete' ? 'badge-success' : 
                                                      item.data_validation === 'moderate' ? 'badge-warning' : 'badge-danger';
                                const validationBadge = `<span class="badge ${validationClass}">${item.data_validation}</span>`;

                                const row = $(`
                                    <tr class="potential-list-row">
                                        <td class="py-3 px-4">
                                            <strong>${item.customer_name || 'Unknown'}</strong>
                                            <br><small class="text-muted">${item.company || '-'}</small>
                                        </td>
                                        <td class="text-center py-3 px-4">${statusBadge}</td>
                                        <td class="text-center py-3 px-4">Rp${formatAmountShort(item.amount || 0)}</td>
                                        <td class="text-center py-3 px-4">${item.regional || '-'}</td>
                                        <td class="text-center py-3 px-4">${item.product || '-'}</td>
                                        <td class="text-center py-3 px-4">${lastActivity}</td>
                                        <td class="text-center py-3 px-4" style="background-color: #f8f9fa;">${validationBadge}</td>
                                    </tr>
                                `);
                                tbody.append(row);
                            });

                            $('#total-list-count').text(totalPotential.toLocaleString());
                            $('#total-list-amount').text('Rp' + formatAmountShort(totalAmount));

                            adjustPotentialListHeight(response.data.length);
                        } else {
                            tbody.html(`
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fas fa-info-circle mb-2" style="font-size: 1.5rem;"></i><br>
                                        <span>No potential dealing list data available for the selected period</span>
                                    </td>
                                </tr>
                            `);
                            
                            $('#total-list-count').text('0');
                            $('#total-list-amount').text('Rp0');

                            adjustPotentialListHeight(0);
                        }

                    }).fail(function(xhr, status, error) {
                        console.error('Error loading potential list table:', error);
                        tbody.html(`
                            <tr>
                                <td colspan="7" class="text-center py-5 text-danger">
                                    <i class="fas fa-exclamation-circle mb-2" style="font-size: 1.5rem;"></i><br>
                                    <span>Error loading potential dealing list data</span>
                                    <br><button class="btn btn-sm btn-outline-primary mt-3" onclick="loadPotentialListTable()">
                                        <i class="fas fa-refresh me-1"></i> Retry
                                    </button>
                                </td>
                            </tr>
                        `);

                        $('#total-list-count').text('0');
                        $('#total-list-amount').text('Rp0');
                    });
                }

                $('#sls-dealing-apply').on('click', function() {

                    @if(auth()->user()->role?->code === 'super_admin')
                        $('#sls-dealing-table-branch').val($('#sls-dealing-branch').val());
                    @endif
                    $('#sls-dealing-table-start-date').val($('#sls-dealing-start-date').val());
                    $('#sls-dealing-table-end-date').val($('#sls-dealing-end-date').val());
                    
                    loadSlsDealingChart();
                    loadSlsDealingTable();
                });

                $('#sls-dealing-table-apply').on('click', function() {
 
                    @if(auth()->user()->role?->code === 'super_admin')
                        $('#sls-dealing-branch').val($('#sls-dealing-table-branch').val());
                    @endif
                    $('#sls-dealing-start-date').val($('#sls-dealing-table-start-date').val());
                    $('#sls-dealing-end-date').val($('#sls-dealing-table-end-date').val());
                    
                    loadSlsDealingChart();
                    loadSlsDealingTable();
                });


                $('#potential-branch-apply').on('click', function() {
                    loadPotentialBranchTable();
                });

                $('#potential-list-apply').on('click', function() {
                    loadPotentialListTable();
                });

                loadSlsDealingChart();
                loadSlsDealingTable();

                loadPotentialBranchTable();
                loadPotentialListTable();
            });
        </script>
    @endsection
