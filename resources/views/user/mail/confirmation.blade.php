Hi {{ $first_name }},
<p> Your registration is complete, Please verify your email </p>
<a href="{{ route('user_confirmation', $token) }}">
<button type="submit" class="log-teal-btn">
  VERIFY EMAIL

</button>
</a>
