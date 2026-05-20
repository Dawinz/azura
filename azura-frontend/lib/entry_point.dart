import 'package:animations/animations.dart';
import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'package:provider/provider.dart';
import 'package:shop/constants.dart';
import 'package:shop/providers/cart_provider.dart';
import 'package:shop/route/screen_export.dart';
import 'package:shop/widgets/tablet_content_width.dart';

class EntryPoint extends StatefulWidget {
  const EntryPoint({super.key});

  @override
  State<EntryPoint> createState() => _EntryPointState();
}

class _EntryPointState extends State<EntryPoint> {
  final List<Widget> _pages = const [
    HomeScreen(),
    DiscoverScreen(),
    BookmarkScreen(),
    CartScreen(),
    ProfileScreen(),
  ];
  int _currentIndex = 0;

  static const double _tabletBreakpoint = 700;

  String _titleForTab(int index) {
    switch (index) {
      case 0:
        return 'Shop';
      case 1:
        return 'Discover';
      case 2:
        return 'Wishlist';
      case 3:
        return 'Cart';
      case 4:
        return 'Profile';
      default:
        return 'Azuramall';
    }
  }

  void _selectTab(int index) {
    if (index != _currentIndex) {
      setState(() => _currentIndex = index);
    }
  }

  SvgPicture _svgIcon(BuildContext context, String src, {Color? color}) {
    return SvgPicture.asset(
      src,
      height: 24,
      colorFilter: ColorFilter.mode(
        color ??
            Theme.of(context).iconTheme.color!.withValues(
                  alpha: Theme.of(context).brightness == Brightness.dark
                      ? 0.3
                      : 1,
                ),
        BlendMode.srcIn,
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final width = MediaQuery.sizeOf(context).width;
    final useRail = width >= _tabletBreakpoint;

    Widget cartIcon({required bool active, required CartProvider cart}) {
      final icon = _svgIcon(
        context,
        'assets/icons/Bag.svg',
        color: active ? primaryColor : null,
      );
      final count = cart.cartQuantity;
      if (count <= 0) return icon;
      return Badge(
        label: Text(
          count > 99 ? '99+' : '$count',
          style: const TextStyle(fontSize: 10),
        ),
        child: icon,
      );
    }

    final pageBody = PageTransitionSwitcher(
      duration: defaultDuration,
      transitionBuilder: (child, animation, secondAnimation) {
        return FadeThroughTransition(
          animation: animation,
          secondaryAnimation: secondAnimation,
          child: child,
        );
      },
      child: KeyedSubtree(
        key: ValueKey<int>(_currentIndex),
        child: TabletContentWidth(child: _pages[_currentIndex]),
      ),
    );

    return Scaffold(
      appBar: AppBar(
        backgroundColor: Theme.of(context).scaffoldBackgroundColor,
        leading: Padding(
          padding: const EdgeInsets.only(left: 8),
          child: Image.asset(
            'assets/logo/logo.jpg',
            height: 28,
            fit: BoxFit.contain,
          ),
        ),
        leadingWidth: 56,
        centerTitle: true,
        title: Text(_titleForTab(_currentIndex)),
        actions: [
          IconButton(
            onPressed: () {
              Navigator.pushNamed(context, searchScreenRoute);
            },
            icon: _svgIcon(context, 'assets/icons/Search.svg'),
          ),
          IconButton(
            onPressed: () {
              Navigator.pushNamed(context, notificationsScreenRoute);
            },
            icon: _svgIcon(context, 'assets/icons/Notification.svg'),
          ),
        ],
      ),
      body: Consumer<CartProvider>(
        builder: (context, cart, _) {
          if (useRail) {
            return Row(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                NavigationRail(
                  selectedIndex: _currentIndex,
                  onDestinationSelected: _selectTab,
                  labelType: NavigationRailLabelType.all,
                  destinations: [
                    NavigationRailDestination(
                      icon: _svgIcon(context, 'assets/icons/Shop.svg'),
                      selectedIcon: _svgIcon(
                        context,
                        'assets/icons/Shop.svg',
                        color: primaryColor,
                      ),
                      label: const Text('Shop'),
                    ),
                    NavigationRailDestination(
                      icon: _svgIcon(context, 'assets/icons/Category.svg'),
                      selectedIcon: _svgIcon(
                        context,
                        'assets/icons/Category.svg',
                        color: primaryColor,
                      ),
                      label: const Text('Discover'),
                    ),
                    NavigationRailDestination(
                      icon: _svgIcon(context, 'assets/icons/Bookmark.svg'),
                      selectedIcon: _svgIcon(
                        context,
                        'assets/icons/Bookmark.svg',
                        color: primaryColor,
                      ),
                      label: const Text('Wishlist'),
                    ),
                    NavigationRailDestination(
                      icon: cartIcon(active: false, cart: cart),
                      selectedIcon: cartIcon(active: true, cart: cart),
                      label: const Text('Cart'),
                    ),
                    NavigationRailDestination(
                      icon: _svgIcon(context, 'assets/icons/Profile.svg'),
                      selectedIcon: _svgIcon(
                        context,
                        'assets/icons/Profile.svg',
                        color: primaryColor,
                      ),
                      label: const Text('Profile'),
                    ),
                  ],
                ),
                const VerticalDivider(width: 1),
                Expanded(child: pageBody),
              ],
            );
          }
          return pageBody;
        },
      ),
      bottomNavigationBar: useRail
          ? null
          : Consumer<CartProvider>(
              builder: (context, cart, _) {
                return SafeArea(
                  child: Align(
                    alignment: Alignment.bottomCenter,
                    child: ConstrainedBox(
                      constraints: const BoxConstraints(maxWidth: 520),
                      child: Container(
                        padding: const EdgeInsets.only(top: defaultPadding / 2),
                        color: Theme.of(context).brightness == Brightness.light
                            ? Colors.white
                            : const Color(0xFF101015),
                        child: BottomNavigationBar(
                          currentIndex: _currentIndex,
                          onTap: _selectTab,
                          backgroundColor:
                              Theme.of(context).brightness == Brightness.light
                                  ? Colors.white
                                  : const Color(0xFF101015),
                          type: BottomNavigationBarType.fixed,
                          selectedFontSize: 12,
                          unselectedFontSize: 11,
                          selectedItemColor: primaryColor,
                          unselectedItemColor:
                              Theme.of(context).colorScheme.onSurfaceVariant,
                          items: [
                            BottomNavigationBarItem(
                              icon: _svgIcon(context, 'assets/icons/Shop.svg'),
                              activeIcon: _svgIcon(
                                context,
                                'assets/icons/Shop.svg',
                                color: primaryColor,
                              ),
                              label: 'Shop',
                            ),
                            BottomNavigationBarItem(
                              icon:
                                  _svgIcon(context, 'assets/icons/Category.svg'),
                              activeIcon: _svgIcon(
                                context,
                                'assets/icons/Category.svg',
                                color: primaryColor,
                              ),
                              label: 'Discover',
                            ),
                            BottomNavigationBarItem(
                              icon: _svgIcon(
                                  context, 'assets/icons/Bookmark.svg'),
                              activeIcon: _svgIcon(
                                context,
                                'assets/icons/Bookmark.svg',
                                color: primaryColor,
                              ),
                              label: 'Wishlist',
                            ),
                            BottomNavigationBarItem(
                              icon: cartIcon(active: false, cart: cart),
                              activeIcon: cartIcon(active: true, cart: cart),
                              label: 'Cart',
                            ),
                            BottomNavigationBarItem(
                              icon:
                                  _svgIcon(context, 'assets/icons/Profile.svg'),
                              activeIcon: _svgIcon(
                                context,
                                'assets/icons/Profile.svg',
                                color: primaryColor,
                              ),
                              label: 'Profile',
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                );
              },
            ),
    );
  }
}
