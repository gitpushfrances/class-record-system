{{-- Keeps ?from= on GET forms of the class pages (a GET form replaces the query string). --}}
<input type="hidden" name="from" value="{{ \App\Services\TeacherNav::from() }}">
