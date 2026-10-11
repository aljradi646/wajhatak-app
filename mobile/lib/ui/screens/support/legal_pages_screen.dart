import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../data/api_client.dart';
import '../../../state/providers.dart';
import '../../widgets.dart';

class LegalPagesScreen extends ConsumerStatefulWidget {
  const LegalPagesScreen({super.key});
  @override ConsumerState<LegalPagesScreen> createState()=>_LegalPagesScreenState();
}
class _LegalPagesScreenState extends ConsumerState<LegalPagesScreen> {
  late Future<List<Map<String,dynamic>>> _future;
  @override void initState(){super.initState();_future=_load();}
  Future<List<Map<String,dynamic>>> _load()async{
    final json=await ref.read(apiClientProvider).get('/pages');
    return (json['data'] as List<dynamic>? ?? const []).whereType<Map<String,dynamic>>().toList();
  }
  @override Widget build(BuildContext context)=>Scaffold(appBar:WajhatakScreenHeader(title:'السياسات والمعلومات'),body:FutureBuilder<List<Map<String,dynamic>>>(future:_future,builder:(context,snapshot){
    if(snapshot.connectionState!=ConnectionState.done)return const ListSkeleton(trailing:true);
    if(snapshot.hasError)return Center(child:FilledButton(onPressed:()=>setState(()=>_future=_load()),child:const Text('إعادة المحاولة')));
    final pages=snapshot.data??const [];
    if(pages.isEmpty)return const EmptyState(title:'لا توجد صفحات منشورة',body:'تحقق من إعداد صفحات الموقع في لوحة المنصة.',icon:Icons.policy_rounded);
    return ListView.separated(padding:const EdgeInsets.all(18),itemCount:pages.length,separatorBuilder:(_,__)=>const SizedBox(height:10),itemBuilder:(context,index){
      final page=pages[index];final slug=page['slug'] as String? ?? '';
      final legal=page['category']=='legal';final help=page['category']=='help';
      return Card(elevation:0,shape:RoundedRectangleBorder(borderRadius:BorderRadius.circular(18),side:BorderSide(color:Theme.of(context).colorScheme.outlineVariant)),child:ListTile(
        contentPadding:const EdgeInsets.all(12),leading:Icon(legal?Icons.verified_user_rounded:(help?Icons.help_center_rounded:Icons.info_rounded),color:legal?Colors.deepPurple:(help?Colors.orange:Colors.teal),size:30),
        title:Text(page['title'] as String? ?? '',style:const TextStyle(fontWeight:FontWeight.w900)),
        subtitle:Padding(padding:const EdgeInsets.only(top:5),child:Text(page['summary'] as String? ?? '',maxLines:2,overflow:TextOverflow.ellipsis)),
        trailing:const Icon(Icons.chevron_left_rounded),
        onTap:()=>Navigator.of(context).push(MaterialPageRoute(builder:(_)=>LegalPageDetailScreen(slug:slug,title:page['title'] as String? ?? ''))),
      ));
    });
  }));
}
class LegalPageDetailScreen extends ConsumerStatefulWidget {
  const LegalPageDetailScreen({super.key,required this.slug,required this.title});
  final String slug;final String title;
  @override ConsumerState<LegalPageDetailScreen> createState()=>_LegalPageDetailScreenState();
}
class _LegalPageDetailScreenState extends ConsumerState<LegalPageDetailScreen> {
  late Future<Map<String,dynamic>> _future;
  @override void initState(){super.initState();_future=_load();}
  Future<Map<String,dynamic>> _load()async{final json=await ref.read(apiClientProvider).get('/pages/'+widget.slug);return json['data'] as Map<String,dynamic>;}
  @override Widget build(BuildContext context)=>Scaffold(appBar:WajhatakScreenHeader(title:widget.title),body:FutureBuilder<Map<String,dynamic>>(future:_future,builder:(context,snapshot){
    if(snapshot.connectionState!=ConnectionState.done)return const ListSkeleton(trailing:true);
    if(snapshot.hasError||!snapshot.hasData)return Center(child:FilledButton(onPressed:()=>setState(()=>_future=_load()),child:const Text('إعادة المحاولة')));
    final page=snapshot.data!;final content=page['content'] as String? ?? '';
    return ListView(padding:const EdgeInsets.fromLTRB(20,18,20,36),children:[
      Text(page['title'] as String? ?? widget.title,style:Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight:FontWeight.w900)),
      if((page['summary'] as String? ?? '').isNotEmpty)Padding(padding:const EdgeInsets.only(top:8,bottom:18),child:Text(page['summary'] as String,style:Theme.of(context).textTheme.bodyMedium?.copyWith(color:Theme.of(context).colorScheme.onSurfaceVariant,height:1.6))),
      Container(padding:const EdgeInsets.all(18),decoration:BoxDecoration(color:Theme.of(context).colorScheme.surface,borderRadius:BorderRadius.circular(18),border:Border.all(color:Theme.of(context).colorScheme.outlineVariant)),child:Text(content,style:Theme.of(context).textTheme.bodyMedium?.copyWith(height:1.9))),
      if(widget.slug=='contact')...[
        const SizedBox(height:16),
        if((page['contact_email'] as String? ?? '').isNotEmpty)ListTile(leading:const Icon(Icons.email_rounded,color:Colors.teal),title:const Text('بريد الدعم'),subtitle:Text(page['contact_email'] as String)),
        if((page['contact_phone'] as String? ?? '').isNotEmpty)ListTile(leading:const Icon(Icons.phone_rounded,color:Colors.orange),title:const Text('هاتف الدعم'),subtitle:Text(page['contact_phone'] as String)),
      ],
      const SizedBox(height:14),Text('آخر تحديث: '+(page['updated_at']?.toString()??'—'),style:Theme.of(context).textTheme.labelSmall?.copyWith(color:Theme.of(context).colorScheme.onSurfaceVariant)),
    ]);
  }));
}
