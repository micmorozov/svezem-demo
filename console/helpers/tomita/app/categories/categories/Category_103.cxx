// Категория 103 - Аренда гидромолота

#encoding "utf-8"
#GRAMMAR_ROOT S

Transp -> AnyWord<kwtype="аренда">;
What -> "гидромолот";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_103);