<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Factura</title>

    <style>
        @page {
            margin: 28px 36px;
        }

        body {
            background: #ffffff none;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #3f3f46;
            line-height: 1.45;
        }

        table {
            border-collapse: collapse;
        }

        .header-band {
            width: 100%;
        }

        .brand {
            font-size: 24px;
            font-weight: bold;
            color: #e6621f;
            letter-spacing: 0.5px;
        }

        .brand-sub {
            font-size: 9px;
            color: #a1a1aa;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .doc-title {
            font-size: 26px;
            font-weight: bold;
            color: #18181b;
            letter-spacing: 3px;
        }

        .meta-table {
            font-size: 10px;
        }

        .meta-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        .meta-label {
            color: #a1a1aa;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding-right: 12px;
        }

        .meta-value {
            color: #18181b;
            font-weight: bold;
        }

        .accent-rule {
            height: 3px;
            background-color: #e6621f;
            font-size: 0;
            line-height: 0;
        }

        .pill {
            display: inline-block;
            padding: 2px 9px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-radius: 3px;
        }

        .pill-paid {
            color: #ffffff;
            background-color: #16a34a;
        }

        .pill-due {
            color: #ffffff;
            background-color: #e6621f;
        }

        .party-panel {
            border: 1px solid #e4e4e7;
            background-color: #fafafa;
            padding: 12px 14px;
        }

        .party-caption {
            font-size: 9px;
            color: #c84c14;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: bold;
            padding-bottom: 6px;
        }

        .party-name {
            font-size: 13px;
            font-weight: bold;
            color: #18181b;
        }

        .party-line {
            color: #52525b;
        }

        .memo {
            color: #52525b;
            font-size: 10px;
        }

        .items {
            width: 100%;
        }

        .items th {
            background-color: #e6621f;
            color: #ffffff;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 8px 10px;
            text-align: left;
        }

        .items th.num {
            text-align: right;
        }

        .items td {
            padding: 9px 10px;
            vertical-align: top;
            border-bottom: 1px solid #e4e4e7;
            color: #3f3f46;
        }

        .items td.num {
            text-align: right;
            white-space: nowrap;
        }

        .item-desc {
            color: #18181b;
            font-weight: bold;
        }

        .dates {
            color: #a1a1aa;
            font-size: 9px;
        }

        .totals {
            width: 100%;
        }

        .totals td {
            padding: 6px 10px;
            font-size: 11px;
        }

        .totals td.label {
            text-align: right;
            color: #52525b;
        }

        .totals td.value {
            text-align: right;
            white-space: nowrap;
            color: #18181b;
            border-bottom: 1px solid #f1f1f4;
        }

        .totals tr.grand td {
            background-color: #fff5ef;
            border-top: 2px solid #e6621f;
            border-bottom: 2px solid #e6621f;
            font-size: 13px;
            font-weight: bold;
            color: #18181b;
            padding: 9px 10px;
        }

        .footer {
            color: #a1a1aa;
            font-size: 9px;
            line-height: 1.6;
            border-top: 1px solid #e4e4e7;
            padding-top: 10px;
        }

        .spacer {
            font-size: 0;
            line-height: 0;
        }
    </style>
</head>
<body>

    {{-- ============ CABECERA ============ --}}
    <table class="header-band">
        <tr valign="top">
            <td width="55%">
                <div class="brand">{{ $header ?? $vendor ?? $invoice->account_name }}</div>
                <div class="brand-sub">Bienestar &amp; estética</div>
            </td>
            <td width="45%" align="right">
                <div class="doc-title">FACTURA</div>
                <div class="spacer">&nbsp;</div>

                <table class="meta-table" align="right">
                    @if ($invoiceId = $id ?? $invoice->number)
                        <tr>
                            <td class="meta-label">Nº de factura</td>
                            <td class="meta-value">{{ $invoiceId }}</td>
                        </tr>
                    @endif

                    <tr>
                        <td class="meta-label">Fecha de emisión</td>
                        <td class="meta-value">{{ $invoice->date()->format('d/m/Y') }}</td>
                    </tr>

                    @if ($dueDate = $invoice->dueDate())
                        <tr>
                            <td class="meta-label">Vencimiento</td>
                            <td class="meta-value">{{ $dueDate->format('d/m/Y') }}</td>
                        </tr>
                    @endif

                    <tr>
                        <td class="meta-label">Estado</td>
                        <td>
                            @if ($invoice->isPaid())
                                <span class="pill pill-paid">Pagada</span>
                            @else
                                <span class="pill pill-due">Pendiente</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="spacer">&nbsp;</div>
    <div class="accent-rule">&nbsp;</div>
    <div class="spacer" style="height: 18px;">&nbsp;</div>

    {{-- ============ EMISOR / CLIENTE ============ --}}
    <table width="100%">
        <tr valign="top">
            <td width="49%">
                <div class="party-panel">
                    <div class="party-caption">Emisor</div>
                    <div class="party-name">{{ $vendor ?? $invoice->account_name }}</div>

                    @isset($street)
                        <div class="party-line">{{ $street }}</div>
                    @endisset

                    @isset($location)
                        <div class="party-line">{{ $location }}</div>
                    @endisset

                    @isset($country)
                        <div class="party-line">{{ $country }}</div>
                    @endisset

                    @isset($phone)
                        <div class="party-line">{{ $phone }}</div>
                    @endisset

                    @isset($email)
                        <div class="party-line">{{ $email }}</div>
                    @endisset

                    @isset($url)
                        <div class="party-line">{{ $url }}</div>
                    @endisset

                    @isset($vendorVat)
                        <div class="party-line"><strong>{{ $vendorVat }}</strong></div>
                    @else
                        @foreach ($invoice->accountTaxIds() as $taxId)
                            <div class="party-line"><strong>{{ $taxId->value }}</strong></div>
                        @endforeach
                    @endisset
                </div>
            </td>

            <td width="2%" class="spacer">&nbsp;</td>

            <td width="49%">
                <div class="party-panel">
                    <div class="party-caption">Facturar a</div>
                    <div class="party-name">{{ $invoice->customer_name ?? $invoice->customer_email }}</div>

                    @if ($address = $invoice->customer_address)
                        @if ($address->line1)
                            <div class="party-line">{{ $address->line1 }}</div>
                        @endif

                        @if ($address->line2)
                            <div class="party-line">{{ $address->line2 }}</div>
                        @endif

                        @if ($address->city)
                            <div class="party-line">{{ $address->city }}</div>
                        @endif

                        @if ($address->state || $address->postal_code)
                            <div class="party-line">{{ implode(' ', array_filter([$address->postal_code, $address->state])) }}</div>
                        @endif

                        @if ($address->country)
                            <div class="party-line">{{ $address->country }}</div>
                        @endif
                    @endif

                    @if ($invoice->customer_phone)
                        <div class="party-line">{{ $invoice->customer_phone }}</div>
                    @endif

                    @if ($invoice->customer_name)
                        <div class="party-line">{{ $invoice->customer_email }}</div>
                    @endif

                    @foreach ($invoice->customerTaxIds() as $taxId)
                        <div class="party-line"><strong>{{ $taxId->value }}</strong></div>
                    @endforeach
                </div>
            </td>
        </tr>
    </table>

    {{-- ============ CONCEPTO / MEMO ============ --}}
    @if (isset($product) || $invoice->description || isset($vat))
        <div class="spacer" style="height: 14px;">&nbsp;</div>

        @isset($product)
            <div class="memo"><strong style="color: #c84c14;">Concepto:</strong> {{ $product }}</div>
        @endisset

        @if ($invoice->description)
            <div class="memo">{{ $invoice->description }}</div>
        @endif

        @if (isset($vat))
            <div class="memo">{{ $vat }}</div>
        @endif
    @endif

    <div class="spacer" style="height: 18px;">&nbsp;</div>

    {{-- ============ LÍNEAS DE FACTURA ============ --}}
    <table class="items">
        <tr>
            <th align="left">Concepto</th>
            <th class="num">Cant.</th>
            <th class="num">Precio unit.</th>

            @if ($invoice->hasTax())
                <th class="num">IVA</th>
            @endif

            <th class="num">Importe</th>
        </tr>

        @foreach ($invoice->invoiceLineItems() as $item)
            <tr>
                <td>
                    <span class="item-desc">{{ $item->description }}</span>

                    @if ($item->hasPeriod() && ! $item->periodStartAndEndAreEqual())
                        <br><span class="dates">{{ $item->startDate() }} — {{ $item->endDate() }}</span>
                    @endif
                </td>

                <td class="num">{{ $item->quantity }}</td>
                <td class="num">{{ $item->unitAmountExcludingTax() }}</td>

                @if ($invoice->hasTax())
                    <td class="num">
                        @if ($inclusiveTaxPercentage = $item->inclusiveTaxPercentage())
                            {{ $inclusiveTaxPercentage }}% incl.
                        @endif

                        @if ($item->hasBothInclusiveAndExclusiveTax())
                            +
                        @endif

                        @if ($exclusiveTaxPercentage = $item->exclusiveTaxPercentage())
                            {{ $exclusiveTaxPercentage }}%
                        @endif
                    </td>
                @endif

                <td class="num">{{ $item->total() }}</td>
            </tr>
        @endforeach
    </table>

    {{-- ============ TOTALES (alineados a la derecha) ============ --}}
    <div class="spacer" style="height: 16px;">&nbsp;</div>

    <table width="100%">
        <tr valign="top">
            <td width="52%">&nbsp;</td>
            <td width="48%">
                <table class="totals">
                    @if ($invoice->hasDiscount() || $invoice->hasTax() || $invoice->hasStartingBalance())
                        <tr>
                            <td class="label">Subtotal</td>
                            <td class="value">{{ $invoice->subtotal() }}</td>
                        </tr>
                    @endif

                    @if ($invoice->hasDiscount())
                        @foreach ($invoice->discounts() as $discount)
                            @php($coupon = $discount->coupon())
                            <tr>
                                <td class="label">
                                    @if ($coupon->isPercentage())
                                        {{ $coupon->name() }} ({{ $coupon->percentOff() }}% dto.)
                                    @else
                                        {{ $coupon->name() }} ({{ $coupon->amountOff() }} dto.)
                                    @endif
                                </td>
                                <td class="value">-{{ $invoice->discountFor($discount) }}</td>
                            </tr>
                        @endforeach
                    @endif

                    @unless ($invoice->isNotTaxExempt())
                        <tr>
                            <td class="label">
                                @if ($invoice->isTaxExempt())
                                    Exento de IVA
                                @else
                                    IVA con inversión del sujeto pasivo
                                @endif
                            </td>
                            <td class="value">&nbsp;</td>
                        </tr>
                    @else
                        @foreach ($invoice->taxes() as $tax)
                            <tr>
                                <td class="label">
                                    {{ $tax->display_name }}{{ $tax->jurisdiction ? ' - '.$tax->jurisdiction : '' }}
                                    ({{ $tax->percentage }}%{{ $tax->isInclusive() ? ' incl.' : '' }})
                                </td>
                                <td class="value">{{ $tax->amount() }}</td>
                            </tr>
                        @endforeach
                    @endunless

                    <tr>
                        <td class="label">Total</td>
                        <td class="value">{{ $invoice->realTotal() }}</td>
                    </tr>

                    @if ($invoice->hasAppliedBalance())
                        <tr>
                            <td class="label">Saldo aplicado</td>
                            <td class="value">{{ $invoice->appliedBalance() }}</td>
                        </tr>
                    @endif

                    <tr class="grand">
                        <td class="label" style="text-align: right; color: #18181b;">Total a pagar</td>
                        <td class="value" style="border-bottom: 0;">{{ $invoice->amountDue() }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ============ PIE LEGAL ============ --}}
    <div class="spacer" style="height: 30px;">&nbsp;</div>

    <div class="footer">
        Documento generado electrónicamente; válido sin firma ni sello.
        @isset($vendor) {{ $vendor }} @endisset
        @isset($vendorVat) · {{ $vendorVat }} @endisset
        @isset($email) · {{ $email }} @endisset
        <br>
        Conserve esta factura como justificante de pago de su suscripción. Para cualquier consulta, responda al correo de envío.
    </div>

</body>
</html>
