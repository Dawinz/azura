/// Production defaults point at the Railway deployment. Override per environment:
///
/// `flutter run --dart-define=API_BASE_URL=https://your-host.example`
/// Codemagic: set `API_BASE_URL` in Environment variables if not using the default.
class AppConfig {
  AppConfig._();

  static const String _defaultApiBase =
      'https://azura-backend-production.up.railway.app';

  /// HTTPS origin only, no trailing slash (no `/index.php` suffix).
  static String get apiBaseUrl {
    const fromEnv = String.fromEnvironment(
      'API_BASE_URL',
      defaultValue: _defaultApiBase,
    );
    var base = fromEnv.trim().replaceAll(RegExp(r'/+$'), '');
    base = base.replaceFirst(RegExp(r'/index\.php$'), '');
    return base;
  }

  /// CodeIgniter entry; FrankenPHP/Caddy on Railway does not apply root `.htaccess` rewrites.
  static String get apiEntryUrl => '$apiBaseUrl/index.php';

  /// Backend-relative upload paths (e.g. `202507/photo.jpg`).
  static String resolveUploadUrl(String path) {
    if (path.startsWith('http://') || path.startsWith('https://')) {
      return path;
    }
    final trimmed = path.replaceFirst(RegExp(r'^/+'), '');
    return '$apiBaseUrl/uploads/$trimmed';
  }
}
