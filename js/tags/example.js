<example>
	<div>
		<h3>{example.title}</h3>
		<div layout="row">
			<pre>
				<code class="language-php">
					{example.code}
				</code>
			</pre>
			<div>
				{example.files}
			</div>
		</div>
	</div>
	<script>

		this.example = opts.item


		this.on(
			'mount',
			function()
			{
				Prism.highlightAll()
			}.bind(this)
		)
	</script>
</example>