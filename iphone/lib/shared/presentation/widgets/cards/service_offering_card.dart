import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:hociatec_mobile/features/services/domain/service_offering.dart';
import 'package:hociatec_mobile/shared/presentation/widgets/cards/card_media.dart';
import 'package:hociatec_mobile/shared/presentation/widgets/fact_paragraph.dart';
import 'package:hociatec_mobile/shared/utils/content_formatters.dart';

class ServiceOfferingCard extends StatelessWidget {
  const ServiceOfferingCard({
    required this.service,
    super.key,
  });

  final ServiceOffering service;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final description = extractFirstSentence(
      service.description,
      'Plus de details disponibles dans la fiche du service.',
    );

    return InkWell(
      borderRadius: BorderRadius.circular(18),
      onTap: () => context.push('/prestations/${service.id}'),
      child: Ink(
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(18),
          border: Border.all(color: const Color(0xFFD6D0C9)),
          boxShadow: const <BoxShadow>[
            BoxShadow(
              color: Color(0x14342718),
              blurRadius: 26,
              offset: Offset(0, 10),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: <Widget>[
            CardMedia(
              imageUrl: service.imageUrl,
              icon: Icons.design_services_outlined,
              background: const Color(0xFFF7F5F2),
              height: 132,
            ),
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 16, 16, 18),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: <Widget>[
                  Text(
                    service.title,
                    style: theme.textTheme.titleMedium?.copyWith(
                      fontWeight: FontWeight.w900,
                      color: const Color(0xFF171C24),
                      height: 1.25,
                    ),
                  ),
                  const SizedBox(height: 10),
                  Text(
                    description,
                    maxLines: 3,
                    overflow: TextOverflow.ellipsis,
                    style: theme.textTheme.bodyMedium?.copyWith(
                      color: const Color(0xFF61574F),
                      height: 1.55,
                    ),
                  ),
                  const SizedBox(height: 14),
                  FactParagraph(
                    label: 'Mode de facturation',
                    value: formatServiceBillingMode(service.unit),
                  ),
                  FactParagraph(
                    label: 'Prix HT',
                    value: formatPriceCents(service.priceCents),
                  ),
                  FactParagraph(
                    label: 'Durée',
                    value: service.durationLabel?.isNotEmpty == true
                        ? service.durationLabel!
                        : 'Sur étude',
                    showDivider: false,
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
