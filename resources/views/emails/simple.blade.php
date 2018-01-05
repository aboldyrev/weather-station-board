<pre>
{!! $content !!}
================================
{!! \Carbon\Carbon::now()->format('H:i d.m.Y') !!}{{ $context ? PHP_EOL . '================================' : NULL}}
{!! $context ? $context : NULL !!}
</pre>