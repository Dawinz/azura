import 'package:flutter/material.dart';
import 'package:flutter_svg/svg.dart';
import 'package:shop/route/route_constants.dart';

class NotificationsScreen extends StatelessWidget {
  const NotificationsScreen({super.key});

  static const List<Map<String, String>> _items = [
    {
      'title': 'Welcome to Azuramall',
      'body': 'Discover new arrivals and deals every day.',
      'time': 'Now',
    },
    {
      'title': 'Price drop alert',
      'body': 'Items in your interests are now on discount.',
      'time': 'Today',
    },
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text("Notifications"),
        actions: [
          IconButton(
            onPressed: () {
              Navigator.pushNamed(context, notificationOptionsScreenRoute);
            },
            icon: SvgPicture.asset(
              "assets/icons/DotsV.svg",
              colorFilter: ColorFilter.mode(
                Theme.of(context).iconTheme.color!,
                BlendMode.srcIn,
              ),
            ),
          )
        ],
      ),
      body: ListView.separated(
        padding: const EdgeInsets.all(16),
        itemCount: _items.length,
        separatorBuilder: (_, __) => const Divider(height: 1),
        itemBuilder: (context, index) {
          final item = _items[index];
          return ListTile(
            contentPadding: const EdgeInsets.symmetric(vertical: 8),
            leading: const CircleAvatar(child: Icon(Icons.notifications)),
            title: Text(item['title']!),
            subtitle: Text(item['body']!),
            trailing: Text(
              item['time']!,
              style: Theme.of(context).textTheme.bodySmall,
            ),
          );
        },
      ),
    );
  }
}
