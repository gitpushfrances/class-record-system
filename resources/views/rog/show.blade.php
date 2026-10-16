<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Report of Grades — {{ $course['Course No.'] }} {{ $course['Course/Year/Section'] }}</title>
<style>
    @page { size: 8.5in 13in; margin: 0.45in 0.5in; }
    * { box-sizing: border-box; }
    body { margin: 0; background: #e5e7eb; color: #000; font-family: Arial, Helvetica, sans-serif; }
    .toolbar { position: sticky; top: 0; z-index: 10; display: flex; flex-wrap: wrap; align-items: center; gap: 10px; padding: 10px 16px; background: #fff; border-bottom: 1px solid #d1d5db; font-size: 13px; }
    .toolbar a, .toolbar button { padding: 7px 14px; font-size: 13px; font-weight: 600; border-radius: 8px; cursor: pointer; text-decoration: none; }
    .btn-light { color: #374151; background: #fff; border: 1px solid #d1d5db; }
    .btn-dark { color: #fff; background: #1f2937; border: 1px solid #1f2937; }
    .hint { color: #6b7280; }
    .notice { max-width: 8.5in; margin: 12px auto 0; padding: 8px 12px; font-size: 13px; color: #166534; background: #dcfce7; border: 1px solid #86efac; border-radius: 8px; }
    .sheet { display: flex; flex-direction: column; width: 8.5in; height: 13in; margin: 16px auto; padding: 0.45in 0.5in; background: #fff; box-shadow: 0 1px 6px rgba(0,0,0,.15); }
    .head { display: flex; justify-content: space-between; align-items: center; height: 0.85in; }
    .head img { height: 0.8in; width: auto; }
    h1 { margin: 6px 0 0; text-align: center; font-size: 13pt; }
    .term { margin: 2px 0 14px; text-align: center; font-size: 10pt; font-weight: bold; }
    .info { display: flex; justify-content: space-between; align-items: flex-start; gap: 0.3in; font-size: 8.5pt; }
    .course { border-collapse: collapse; }
    .course td { padding: 1px 0; vertical-align: bottom; }
    .course .k { width: 1.35in; font-weight: bold; }
    .course .s { width: 0.12in; }
    .course .v { width: 2.4in; padding-left: 4px; font-weight: bold; border-bottom: 1px solid #000; }
    .legend { border-collapse: collapse; }
    .legend caption { padding-bottom: 4px; font-weight: bold; }
    .legend th { padding: 0 16px 2px 0; text-align: left; text-decoration: underline; font-weight: bold; }
    .legend td { padding: 1px 16px 1px 0; }
    table.grades { width: 100%; margin-top: 14px; border-collapse: collapse; table-layout: fixed; font-size: 8.5pt; }
    table.grades th, table.grades td { height: 0.235in; padding: 0 4px; border: 1px solid #000; overflow: hidden; white-space: nowrap; }
    table.grades th { text-align: center; font-weight: bold; }
    table.grades .c { text-align: center; }
    table.grades .group { text-align: center; font-weight: bold; }
    .signs { display: flex; justify-content: space-between; margin-top: 0.15in; font-size: 7.5pt; }
    .signs > div { width: 24%; }
    .signs .cap { margin-bottom: 0.3in; }
    .signs .name { min-height: 1.2em; font-weight: bold; }
    .foot { margin-top: auto; font-size: 8.5pt; }
    .foot .rule { width: 2in; margin-bottom: 4px; border-top: 2px solid #15803d; }
    .foot .row { display: flex; justify-content: space-between; }
    @media print {
        body { background: #fff; }
        .toolbar, .notice { display: none; }
        .sheet { width: auto; height: 12.1in; margin: 0; padding: 0; box-shadow: none; break-after: page; page-break-after: always; }
        .sheet:last-of-type { break-after: auto; page-break-after: auto; }
        * { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>
</head>
<body>

<div class="toolbar">
    <a class="btn-light" href="{{ $backUrl }}">&larr; Back</a>
    <button type="button" class="btn-dark" onclick="window.print()">Print / Save as PDF</button>
    <span class="hint">In the print dialog choose paper size 8.5 × 13 in (Long) and turn off "Headers and footers".</span>
    @if(blank($schedule))
        <span style="margin-left:auto; color:#b45309;">Schedule of Classes is not set yet. The Program Head sets it when assigning this subject.</span>
    @endif
</div>

@if(session('status'))
    <div class="notice">{{ session('status') }}</div>
@endif

@php
    $legend = [
        ['95-100%', '1.0', 'Outstanding'],
        ['90-94%', '1.1 - 1.5', 'Excellent'],
        ['85-89%', '1.6 - 2.0', 'Very Good'],
        ['80-84%', '2.1 - 2.5', 'Good'],
        ['75-79%', '2.6 - 3.0', 'Fair'],
        ['70-74%', '3.1 - 3.5', 'Conditional'],
        ['55-69%', '3.6 - 5.0', 'Failed'],
        ['INC', 'INC', 'Incomplete'],
        ['Dr', 'Dr', 'Dropped'],
        ['WP', 'WP', 'Withdrawn with Permission'],
        ['IP', 'IP', 'In Progress'],
    ];
    $totalPages = count($pages);
@endphp

@foreach($pages as $index => $page)
<section class="sheet">
    <div class="head">
        <img src="{{ $logoLeft }}" alt="Eastern Samar State University">
        <img src="{{ $logoRight }}" alt="Bagong Pilipinas">
    </div>

    <h1>REPORT OF GRADES</h1>
    <div class="term">{{ $termLine }}</div>

    <div class="info">
        <table class="course">
            @foreach($course as $key => $value)
                <tr><td class="k">{{ $key }}</td><td class="s">:</td><td class="v">{{ $value }}</td></tr>
            @endforeach
        </table>

        <table class="legend">
            <caption>GRADING SYSTEM</caption>
            <tr><th>Actual<br>Rating</th><th>Equivalent<br>Rating</th><th>Adjectival<br>Rating</th></tr>
            @foreach($legend as [$actual, $equivalent, $adjectival])
                <tr><td>{{ $actual }}</td><td>{{ $equivalent }}</td><td>{{ $adjectival }}</td></tr>
            @endforeach
        </table>
    </div>

    <table class="grades">
        <colgroup>
            <col style="width:0.3in"><col><col style="width:1in"><col style="width:0.75in"><col style="width:0.75in"><col style="width:0.75in"><col style="width:0.8in">
        </colgroup>
        <tr><th></th><th>Name of Students</th><th>Student No.</th><th>Midterm</th><th>Finals</th><th>Average</th><th>Remarks</th></tr>
        <tr><td></td><td class="group">{{ $page['label'] }}</td><td></td><td></td><td></td><td></td><td></td></tr>
        @for($r = 0; $r < \App\Services\ReportOfGrades::ROWS_PER_PAGE; $r++)
            @php $row = $page['rows'][$r] ?? null; @endphp
            <tr>
                <td class="c">{{ $row['no'] ?? '' }}</td>
                <td>{{ $row['name'] ?? '' }}</td>
                <td class="c">{{ $row['number'] ?? '' }}</td>
                <td class="c">{{ $row['midterm'] ?? '' }}</td>
                <td class="c">{{ $row['finals'] ?? '' }}</td>
                <td class="c">{{ $row['average'] ?? '' }}</td>
                <td class="c">{{ $row['remarks'] ?? '' }}</td>
            </tr>
        @endfor
    </table>

    <div class="signs">
        @foreach($signatories as $signatory)
            <div>
                <div class="cap">{{ $signatory['caption'] }}</div>
                <div class="name">{{ $signatory['name'] }}</div>
                <div>{{ $signatory['title'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="foot">
        <div class="rule"></div>
        <div>{{ $settings['rog_form_code'] }} | {{ $settings['rog_form_version'] }}</div>
        <div class="row">
            <span>Effectivity Date: {{ $settings['rog_effectivity'] }}</span>
            <span>Page {{ $index + 1 }} of {{ $totalPages }}</span>
        </div>
    </div>
</section>
@endforeach

</body>
</html>
