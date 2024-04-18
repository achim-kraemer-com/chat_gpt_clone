const Encore = require('@symfony/webpack-encore');

const OfflinePlugin = require("@lcdp/offline-plugin");
// var imageCacheBuster = (Math.random() + 1).toString(36).substring(7);
const manifestOptions = {
    name: "prompt privacy portal", // Erscheint unter anderem im App-Startbildschirm auf Android.
    short_name: "prompt privacy portal", // App-Titel z.b. im Android App-Drawer.
    description: "prompt privacy portal PWA",
    // Einfärbungen der Browserleiste.
    theme_color: "#FFFFFF",
    background_color: "#FFFFFF",
    display: "minimal-ui", // Browser stellt im App-Modus nur einen Reload und Vor/Zurückbutton bereit.
    prefer_related_applications: false, // Legt fest das keine alternativ Apps zur Installation angeboten werden sollen.
    start_url: "/chat/", // Diese URL wird beim öffnen der App geladen. Könnte z.b. auch /?pwa=true sein falls man es tracken möchte.
    icons: [
        {
            src: "/build/images/chatgpt-mso-digital_favicon_io/android-chrome-192x192.png",
            sizes: "192x192",
            type: "image/png",
        },
        {
            src: "/build/images/chatgpt-mso-digital_favicon_io/android-chrome-512x512.png",
            sizes: "512x512",
            type: "image/png",
        },
        {
            src: "/build/images/chatgpt-mso-digital_favicon_io/android-chrome-512x512.png",
            sizes: "512x512",
            type: "image/png",
            purpose: "any maskable", // Leg fest das das Icon maskierbar ist, also von Android in den diversen Stilen zugeschnitten werden darf. @see https://maskable.app/editor
        },
    ],
};

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
    .setPublicPath('/build')
    // only needed for CDN's or subdirectory deploy
    //.setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    .addEntry('app', './assets/app.js')
    .addEntry('chat', './assets/chat.js')
    .addEntry('chatHistory', './assets/styles/chatHistory.css')
    .addEntry('faq', './assets/styles/faq.css')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    // enables hashed filenames (e.g. app.abc123.css)
    .enableVersioning(Encore.isProduction())

    // configure Babel
    // .configureBabel((config) => {
    //     config.plugins.push('@babel/a-babel-plugin');
    // })

    // enables and configure @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.23';
    })

    .copyFiles( [
        { from: './assets/images', to: 'images/[path][name].[ext]' },
    ])

    // enables Sass/SCSS support
    //.enableSassLoader()

    // uncomment if you use TypeScript
    //.enableTypeScriptLoader()

    // uncomment if you use React
    //.enableReactPreset()

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    //.enableIntegrityHashes(Encore.isProduction())

    // uncomment if you're having problems with a jQuery plugin
    //.autoProvidejQuery()


    //For manifest.json
    .configureFilenames({
        js: '[name].[contenthash].js',
        css: '[name].[contenthash].css',
        assets: 'assets/[name].[hash:8].[ext]'
    })
        // Manifest hinzufügen.
        .configureManifestPlugin((options) => {
            options.seed = manifestOptions;
        });

    const webpackConfig = Encore.getWebpackConfig();

    webpackConfig.plugins.push(new OfflinePlugin({
        strategy: 'changed',
        responseStrategy: 'cache-first', // Bedeutet das der Nutzer die Daten erst aus den Cache laden soll und als Fallback aus dem Web.
        caches: {
            main: [
                '*.css',
                '*.js',
                'fonts/*',
                'images/*'
            ]
        },
        ServiceWorker: {
            output: '../sw.js' // Ausgabe-Pfad des Service-Worker, der Service Worker muss direkt im "/public"-Verzeichnis liegen, er darf sich nicht in einem Unterordner befinden.
        }
    }))
;

module.exports = Encore.getWebpackConfig();
