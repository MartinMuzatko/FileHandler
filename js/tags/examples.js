<examples>
	<div name="slides">
		<div each={example in examples}>
			<example item={example}></example>
		</div>
	</div>
	<script>

		this.examples = 
		[
			{
				title : 'Batch Create',
				code : "\n\
$customers = [1,2,3,4,5,6,7]; \n\
foreach ($customers as $customer) \n\
\{ \n\
	$file = new File($customer.'/info.json'); \n\
	$file \n\
		->create() \n\
		->write('\{title:\"read instructions\"\}') \n\
		->chmod(0644); \n\
\} \n\
				",
				files : {1:['info.json'],2:['info.json'],3:['info.json'],4:['info.json'],5:['info.json'],6:['info.json'],7:['info.json']},
			},
/*			{
				title : 'Batch Create',
				code : 'mow',
				files : '',
			},
			{
				title : 'Batch Create',
				code : 'mow',
				files : '',
			},*/
		]
		window.app = this

		this.on(
			'mount',
			function()
			{
				console.log('slick init');
				console.log(this.slides);
				$(this.slides).slick(
					{
						autoplay: true,
						autoplaySpeed: 6000,
						speed: 1000,
						dots: true
					}
				)
			}.bind(this)
		)
	</script>
</examples>