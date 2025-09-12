const { src, dest, series } = require( 'gulp' );
const replace = require( 'gulp-replace' );
const { exec } = require( 'child_process' );

const replacementPath = {
	phpFiles: './**/*.php',
	exclude: [ '!./node_modules/**', '!./vendor/**', '!./languages/**' ],
	mainPluginFile: './wp-fusion.php',
	readmeFile: './readme.txt',
	potFile: './languages/wp-fusion.pot',
};

function getVersionFromReadme() {
	const fs = require( 'fs' );
	const readmeContent = fs.readFileSync( replacementPath.readmeFile, 'utf8' );
	const match = readmeContent.match( /^Stable tag:\s*(.+)$/m );
	if ( ! match ) {
		throw new Error( 'Version not found in readme.txt' );
	}
	return match[ 1 ].trim();
}

function updatePHPDocs() {
	const version = getVersionFromReadme();
	return src( [ replacementPath.phpFiles, ...replacementPath.exclude ] )
		.pipe( replace( /\bx\.x(?:\.x)?\b/g, version ) )
		.pipe( dest( './' ) );
}

function updateMainPluginFile() {
	const version = getVersionFromReadme();
	return src( replacementPath.mainPluginFile, { base: './' } )
		.pipe( replace( /^Version:\s*.+$/m, `Version: ${ version }` ) )
		.pipe( dest( './' ) );
}

function updateVersionConstant() {
	const version = getVersionFromReadme();
	return src( replacementPath.mainPluginFile, { base: './' } )
		.pipe(
			replace(
				/(define\(\s*['"]WP_FUSION_VERSION['"],\s*['"]).+?(['"]\s*\);)/,
				`$1${ version }$2`
			)
		)
		.pipe( dest( './' ) );
}

function generatePotFile( done ) {
	const command = `php -d memory_limit=512M -d error_reporting='E_ALL & ~E_DEPRECATED' $(command -v wp) i18n make-pot ./ ./languages/wp-fusion.pot --exclude=node_modules,vendor --allow-root`;

	exec( command, { shell: '/bin/zsh' }, ( err, stdout, stderr ) => {
		if ( err ) {
			const fatalError = stderr.split('\n').filter(line => !line.includes('Deprecated')).join('\n');
			if (fatalError.trim()) {
				console.error( `Error: ${ fatalError }` );
			} else if (stdout && stdout.includes('Fatal error')) {
				console.error( `Error: ${ stdout }`);
			}
			done( err );
		} else {
			console.log( stdout );
			done();
		}
	} );
}

exports.default = series(
	updatePHPDocs,
	updateMainPluginFile,
	updateVersionConstant,
	generatePotFile
);
