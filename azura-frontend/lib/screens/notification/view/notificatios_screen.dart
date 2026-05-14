import 'package:flutter/material.dart';
import 'package:flutter_svg/svg.dart';
import 'package:shop/api/api_service.dart';
import 'package:shop/route/route_constants.dart';
import 'package:shop/services/storage_service.dart';

class NotificationsScreen extends StatefulWidget {
  const NotificationsScreen({super.key});

  @override
  State<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends State<NotificationsScreen> {
  late Future<List<NotificationFeedItem>> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<List<NotificationFeedItem>> _load() async {
    final user = await StorageService.getUser();
    final uid = int.tryParse(user?.id ?? '') ?? 0;
    if (uid < 1) return [];
    return ApiService.getNotificationsFeed(uid);
  }

  Future<void> _reload() async {
    setState(() => _future = _load());
    await _future;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
        actions: [
          IconButton(
            onPressed: () {
              Navigator.pushNamed(context, notificationOptionsScreenRoute);
            },
            icon: SvgPicture.asset(
              'assets/icons/DotsV.svg',
              colorFilter: ColorFilter.mode(
                Theme.of(context).iconTheme.color!,
                BlendMode.srcIn,
              ),
            ),
          )
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _reload,
        child: FutureBuilder<List<NotificationFeedItem>>(
          future: _future,
          builder: (context, snap) {
            if (snap.connectionState == ConnectionState.waiting) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: const [
                  SizedBox(height: 120),
                  Center(child: CircularProgressIndicator()),
                ],
              );
            }
            final items = snap.data ?? [];
            if (items.isEmpty) {
              return ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsets.all(24),
                children: [
                  const SizedBox(height: 48),
                  Icon(
                    Icons.notifications_none_outlined,
                    size: 56,
                    color: Theme.of(context).colorScheme.outline,
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'No notifications yet. When you place orders, updates will appear here.',
                    textAlign: TextAlign.center,
                    style: Theme.of(context).textTheme.bodyLarge,
                  ),
                ],
              );
            }
            return ListView.separated(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(16),
              itemCount: items.length,
              separatorBuilder: (_, __) => const Divider(height: 1),
              itemBuilder: (context, index) {
                final item = items[index];
                return ListTile(
                  contentPadding: const EdgeInsets.symmetric(vertical: 8),
                  leading: const CircleAvatar(child: Icon(Icons.notifications)),
                  title: Text(item.title),
                  subtitle: Text(item.body),
                  trailing: Text(
                    item.time.length >= 10
                        ? item.time.substring(0, 10)
                        : item.time,
                    style: Theme.of(context).textTheme.bodySmall,
                  ),
                );
              },
            );
          },
        ),
      ),
    );
  }
}
