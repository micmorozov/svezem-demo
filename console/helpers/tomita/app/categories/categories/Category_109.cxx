// Категория 109 - Аренда JCB

#encoding "utf-8"
#GRAMMAR_ROOT S

Transp -> AnyWord<kwtype="аренда">;
What -> "jcb" | "джисиби";

Fact -> Transp (AnyWord+) What;

S -> Fact interp(Category.Category_109);