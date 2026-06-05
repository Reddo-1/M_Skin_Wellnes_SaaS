<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consentimiento informado - {{ $client->name }}</title>
    <style>
        @page { margin: 60px 50px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; line-height: 1.5; }
        h1 { font-size: 18px; margin: 0 0 8px; color: #c84c14; }
        h2 { font-size: 13px; margin: 24px 0 8px; color: #82310f; border-bottom: 1px solid #f7a872; padding-bottom: 4px; }
        .meta { color: #898989; font-size: 10px; margin-bottom: 24px; }
        .center-block { margin-bottom: 16px; }
        .grid { width: 100%; display: table; }
        .grid .col { display: table-cell; width: 50%; padding-right: 12px; vertical-align: top; }
        .row { margin-bottom: 4px; }
        .row .label { color: #898989; font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; }
        .row .value { color: #333; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ededee; padding: 6px 8px; text-align: left; vertical-align: top; font-size: 10px; }
        th { background: #fef4ee; color: #82310f; }
        .yes { color: #2e7d32; font-weight: bold; }
        .no { color: #c62828; font-weight: bold; }
        .rgpd-item { margin-bottom: 8px; }
        .signature-block { margin-top: 16px; }
        .signature-img { width: 240px; height: 120px; object-fit: contain; border: 1px solid #cccccc; padding: 4px; }
        .signature-meta { font-size: 10px; color: #898989; margin-top: 4px; }
        .footer { margin-top: 18px; font-size: 9px; color: #898989; text-align: center; }
        .header { width: 100%; display: table; margin-bottom: 8px; }
        .header .brand { display: table-cell; vertical-align: middle; width: 70%; }
        .header .logo { display: table-cell; vertical-align: middle; text-align: right; width: 30%; }
        .header .logo img { max-height: 70px; max-width: 180px; }
        .legal { margin-top: 24px; padding: 12px 14px; background: #fef4ee; border-left: 3px solid #c84c14; font-size: 10px; text-align: justify; }
        .legal h2 { margin-top: 0; border-bottom: none; padding-bottom: 0; }
        .legal p { margin: 6px 0; }
        .declaration { margin-top: 18px; padding: 12px 14px; border: 1px solid #ededee; font-size: 11px; line-height: 1.6; text-align: justify; }
        .declaration .clauses { margin: 8px 0 0 16px; }
        .declaration .clauses li { margin-bottom: 4px; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">
            <h1>Consentimiento informado</h1>
            <p class="meta">
                Centro <strong>{{ $center->name }}</strong> — Documento generado el {{ $signedAt->format('d/m/Y H:i') }}
            </p>
        </div>
        @if ($centerLogo)
            <div class="logo">
                <img src="{{ $centerLogo }}" alt="Logo del centro" />
            </div>
        @endif
    </div>

    <div class="center-block">
        <h2>Datos del paciente</h2>
        <div class="grid">
            <div class="col">
                <div class="row"><div class="label">Nombre y apellidos</div><div class="value">{{ $client->name }}</div></div>
                <div class="row"><div class="label">Correo</div><div class="value">{{ $client->email ?? '—' }}</div></div>
            </div>
            <div class="col">
                <div class="row"><div class="label">Teléfono</div><div class="value">{{ $client->phone ?? '—' }}</div></div>
                <div class="row"><div class="label">Fecha de nacimiento</div><div class="value">{{ $client->birth_date?->format('d/m/Y') ?? '—' }}</div></div>
            </div>
        </div>
    </div>

    <h2>Consentimiento por tratamiento</h2>
    <table>
        <thead>
            <tr>
                <th>Tratamiento</th>
                <th>Consentimiento del paciente</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($treatments as $t)
                <tr>
                    <td>{{ $t['name'] }}</td>
                    <td>
                        @if ($t['treatment_consent'])
                            <span class="yes">Acepta</span>
                        @else
                            <span class="no">No acepta</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Consentimientos RGPD / LOPD-GDD</h2>
    <div class="rgpd-item">
        Uso de fotografías clínicas para seguimiento del tratamiento:
        @if ($rgpd['clinical_photos_consent'])
            <span class="yes">Acepta</span>
        @else
            <span class="no">No acepta</span>
        @endif
    </div>
    <div class="rgpd-item">
        Uso de los datos para comunicaciones comerciales y de marketing del centro:
        @if ($rgpd['marketing_data_consent'])
            <span class="yes">Acepta</span>
        @else
            <span class="no">No acepta</span>
        @endif
    </div>
    <div class="rgpd-item">
        Uso de imágenes con fines comerciales o de promoción del centro:
        @if ($rgpd['commercial_images_consent'])
            <span class="yes">Acepta</span>
        @else
            <span class="no">No acepta</span>
        @endif
    </div>

    @if ($notes)
        <h2 class="">Observaciones</h2>
        <p>{{ $notes }}</p>
    @endif

    <div class="legal page-break">
        <h2>Marco normativo</h2>
        <p>
            El presente consentimiento se emite conforme a la <strong>Ley 41/2002, de 14 de noviembre, básica reguladora
            de la autonomía del paciente y de derechos y obligaciones en materia de información y documentación
            clínica</strong> (en particular su artículo 8, relativo al consentimiento informado).
        </p>
        <p>
            El tratamiento de los datos personales recogidos en este documento se rige por el
            <strong>Reglamento (UE) 2016/679 (RGPD)</strong> y la <strong>Ley Orgánica 3/2018, de 5 de diciembre,
            de Protección de Datos Personales y garantía de los derechos digitales (LOPDGDD)</strong>. El responsable
            del tratamiento es {{ $center->name }}, ante quien el interesado puede ejercer en cualquier momento los
            derechos de acceso, rectificación, supresión, oposición, limitación y portabilidad.
        </p>
        <p>
            El consentimiento aquí prestado es libre, específico, informado e inequívoco, y puede ser
            <strong>revocado en cualquier momento</strong> sin que ello afecte a la licitud del tratamiento basado en
            el consentimiento previo a su retirada.
        </p>
    </div>

    <div class="declaration">
        <p>
            D./Dña. <strong>{{ $client->name }}</strong>, en pleno uso de mis facultades, declaro:
        </p>
        <ol class="clauses">
            <li>
                Haber sido informado/a de forma comprensible por el personal de {{ $center->name }} sobre la naturaleza,
                finalidad, beneficios esperados y riesgos previsibles de cada tratamiento marcado en el presente
                documento, así como de las alternativas disponibles.
            </li>
            <li>
                Haber tenido la oportunidad de formular cuantas preguntas he considerado necesarias y haber recibido
                respuestas satisfactorias a las mismas.
            </li>
            <li>
                Comprender y aceptar las consecuencias de las decisiones manifestadas en los apartados anteriores,
                incluida la información relativa al tratamiento de mis datos personales bajo los regímenes RGPD y
                LOPDGDD.
            </li>
            <li>
                Saber que puedo revocar este consentimiento en cualquier momento, dirigiéndome al centro por escrito
                o por los canales habilitados al efecto.
            </li>
        </ol>
        <p style="margin-top:10px;">
            En consecuencia, <strong>presto mi consentimiento y firmo el presente documento</strong> tras haber leído
            y aceptado todos los términos y condiciones recogidos en él.
        </p>
    </div>

    <div class="signature-block">
        <h2>Firma del paciente</h2>
        <img class="signature-img" src="data:image/png;base64,{{ $signatureBase64 }}" alt="Firma" />
        <p class="signature-meta">
            Firmado por <strong>{{ $client->name }}</strong> el {{ $signedAt->format('d/m/Y H:i') }}.
            Revisado por <strong>{{ $reviewer->name }}</strong>.
        </p>
    </div>

    <p class="footer">
        Este documento ha sido firmado electrónicamente por el paciente en presencia del personal del centro.
        Una copia queda archivada en el sistema del centro.
    </p>
</body>
</html>
